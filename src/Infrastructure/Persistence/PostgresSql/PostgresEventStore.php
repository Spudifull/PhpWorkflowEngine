<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Infrastructure\Persistence\PostgresSql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Spudifull\PhpWorkflowEngine\Domain\Event\AbstractEvent;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Throwable;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\ConcurrencyException;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\WorkflowNotFoundException;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Repository\EventStoreInterface;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;
use Spudifull\PhpWorkflowEngine\Infrastructure\Serializer\EventSerializer;

final class PostgresEventStore implements EventStoreInterface
{
    private const string TABLE_NAME = 'event_store';
    private const array PARAM_TYPES = ['string', 'integer', 'string', 'string', 'string'];

    private string $quotedTable {
        get {
            return $this->quotedTable ??= $this->connection->quoteSingleIdentifier(self::TABLE_NAME);
        }
    }

    public function __construct(
        private readonly Connection $connection,
        private readonly EventSerializer $serializer
    ) {}

    /**
     * @param WorkflowId $id
     * @param EventStream $events
     * @return void
     * @throws Throwable
     * @throws Exception
     */
    public function append(WorkflowId $id, EventStream $events): void
    {
        if ($events->isEmpty()) {
            return;
        }

        $this->connection->beginTransaction();

        try {
            $currentVersion = $this->getCurrentVersion($id);
            $this->insertEvents($id, $events, $currentVersion);
            $this->connection->commit();

        } catch (UniqueConstraintViolationException) {
            $this->connection->rollBack();
            throw ConcurrencyException::forWorkflow($id, $currentVersion);
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @param WorkflowId $id
     * @return EventStream
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function load(WorkflowId $id): EventStream
    {
        $rows = $this->connection->fetchAllAssociative(
            "select type, data from {$this->quotedTable} where workflow_id = ? order by version asc",
            [(string) $id]
        );

        if (empty($rows)) {
            throw WorkflowNotFoundException::withId($id);
        }

        $events = array_map(
            fn(array $row) => $this->serializer->deserialize($row['data'], $row['type']),
            $rows
        );

        return new EventStream($events);
    }

    /**
     * @param WorkflowId $id
     * @return bool
     * @throws Exception
     */
    public function has(WorkflowId $id): bool
    {
        $result = $this->connection->fetchOne(
            "select 1 from {$this->quotedTable} where workflow_id = ? limit 1",
            [(string) $id]
        );

        return $result !== false;
    }

    /**
     * @param WorkflowId $id
     * @return int
     * @throws Exception
     */
    private function getCurrentVersion(WorkflowId $id): int
    {
        return (int) $this->connection->fetchOne(
            "select coalesce(max(version), 0) FROM {$this->quotedTable} WHERE workflow_id = ?",
            [(string) $id]
        );
    }

    /**
     * @param WorkflowId $id
     * @param EventStream $events
     * @param int $startVersion
     * @return void
     * @throws Exception
     * @throws ExceptionInterface
     */
    private function insertEvents(WorkflowId $id, EventStream $events, int $startVersion): void
    {
        $sql = "insert into {$this->quotedTable} (workflow_id, version, type, data, occurred_at) values";

        $params = [];
        $types = [];
        $placeholders = [];

        $version = $startVersion;

        foreach ($events as $event) {
            /** @var AbstractEvent $event */
            $version++;
            $placeholders[] = '(?, ?, ?, ?, ?)';

            array_push($params,
                (string) $id,
                $version,
                $event::class,
                $this->serializer->serialize($event),
                $event->occurredDt->format('Y-m-d H:i:s.u')
            );

            array_push($types, ...self::PARAM_TYPES);
        }

        $this->connection->executeStatement(
            $sql . implode(', ', $placeholders),
            $params,
            $types
        );
    }
}