<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\Runtime;

use Fiber;
use Iterator;
use Throwable;

use Spudifull\PhpWorkflowEngine\Application\DTO\ActivityRequest;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\NonDeterministicWorkflowException;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Workflow\WorkflowContextInterface;

final class WorkflowContext implements WorkflowContextInterface
{
    private Iterator $historyIterator;

    public function __construct(EventStream $history)
    {
        $this->historyIterator = $history->getIterator();
        $this->historyIterator->rewind();
    }

    /**
     * @param string $activityClass
     * @param array $args
     *
     * @return mixed
     *
     * @throws NonDeterministicWorkflowException
     * @throws Throwable
     */
    public function executeActivity(string $activityClass, array $args = []): mixed
    {
        while ($this->historyIterator->valid()) {
            $event = $this->historyIterator->current();
            $this->historyIterator->next();

            if (!$event instanceof ActivityCompleted) {
                continue;
            }

            if ($event->activityName !== $activityClass) {
                throw NonDeterministicWorkflowException::activityMismatch(
                    expected: $activityClass,
                    actual: $event->activityName
                );
            }

            return $event->result;
        }

        return Fiber::suspend(new ActivityRequest($activityClass, $args));
    }
}