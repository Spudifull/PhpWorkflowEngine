<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\Service;

use Exception;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowStarted;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Repository\EventStoreInterface;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final readonly class WorkflowEngine
{
    public function __construct(
        private EventStoreInterface $eventStore
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

        $this->eventStore->append($id, new EventStream([$event]));

        return $id;
    }
}