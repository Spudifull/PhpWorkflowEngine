<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\Service;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

use Spudifull\PhpWorkflowEngine\Application\DTO\ActivityRequest;
use Spudifull\PhpWorkflowEngine\Application\Runtime\WorkflowRunner;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityScheduled;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowFailed;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowStarted;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\CorruptedHistoryException;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\WorkflowClassNotFoundException;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Repository\EventStoreInterface;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final readonly class WorkflowExecutor
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private WorkflowRunner $runner,
        private ContainerInterface $container
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws CorruptedHistoryException
     * @throws WorkflowClassNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function run(WorkflowId $id): void
    {
        $history = $this->eventStore->load($id);

        $historyIterator = $history->getIterator();
        $historyIterator->rewind();
        $initialEvent = $historyIterator->current();

        if (!$initialEvent instanceof WorkflowStarted) {
            throw CorruptedHistoryException::missingWorkflowStarted($id);
        }

        $workflowClass = $initialEvent->workflowName;
        if (!class_exists($workflowClass)) {
            throw WorkflowClassNotFoundException::for($workflowClass);
        }

        $workflow = $this->container->get($workflowClass);

        if (!method_exists($workflow, 'run')) {
            throw WorkflowClassNotFoundException::missingRunMethod($workflowClass);
        }

        try {
            $output = $this->runner->run($workflow, 'run', $history, [$initialEvent->input]);
        } catch (Throwable $e) {
            $event = new WorkflowFailed(
                workflowId: $id,
                error: $e->getMessage(),
                stackTrace: $e->getTraceAsString()
            );
            $this->eventStore->append($id, new EventStream([$event]));
            throw $e;
        }

        if ($output instanceof ActivityRequest) {
            $event = new ActivityScheduled(
                workflowId: $id,
                activityName: $output->name,
                args: $output->args
            );
            $this->eventStore->append($id, new EventStream([$event]));

            // TODO: Здесь мы бы отправили сообщение в RabbitMQ для ActivityWorker
        } else {
            $event = new WorkflowCompleted(
                workflowId: $id,
                result: $output
            );
            $this->eventStore->append($id, new EventStream([$event]));
        }
    }
}