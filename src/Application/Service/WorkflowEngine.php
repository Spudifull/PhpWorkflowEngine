<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\Service;

use Exception;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowStarted;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Repository\EventStoreInterface;
use Spudifull\PhpWorkflowEngine\Domain\Repository\OutboxRepositoryInterface;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final readonly class WorkflowEngine
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private OutboxRepositoryInterface $outboxRepository,
        private Connection $connection,
    ) {}

    /**
     * Запускает новый экземпляр Workflow.
     * Метод не выполняет код Workflow сразу, а только планирует его (создает событие).
     *
     * @var string $workflowClass
     * @var array $input
     * @throws Exception
     *
     * @return WorkflowId
     */
    public function start(string $workflowClass, array $input = []): WorkflowId
    {
        $id = WorkflowId::generate();

        $event = new WorkflowStarted(
            workflowId: $id,
            workflowName: $workflowClass,
            input: $input
        );

        $this->connection->beginTransaction();

        try {
        $this->eventStore->append($id, new EventStream([$event]));

        $this->outboxRepository->schedule(
            queueName: 'workflow_tasks',
            payload: (string) $id
        );

        $this->connection->commit();

        return $id;

        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }

        $this->eventStore->append($id, new EventStream([$event]));

        return $id;
    }
}