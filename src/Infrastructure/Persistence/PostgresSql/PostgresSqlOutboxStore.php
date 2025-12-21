<?php

namespace Spudifull\PhpWorkflowEngine\Infrastructure\Persistence\PostgresSql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Spudifull\PhpWorkflowEngine\Domain\Repository\OutboxRepositoryInterface;

final readonly class PostgresSqlOutboxStore implements OutboxRepositoryInterface
{
    public function __construct(private Connection $connection) {}

    /**
     * @param string $queueName
     * @param string $payload
     * @throws Exception
     */
    public function schedule(string $queueName, string $payload): void
    {
        $this->connection->insert('outbox', [
            'queue_name' => $queueName,
            'payload' => $payload,
            'created_dt' => new \DateTimeImmutable()->format('Y-m-d H:i:s')
        ]);
    }
}