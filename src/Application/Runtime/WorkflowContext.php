<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\Runtime;

use Fiber;
use Iterator;
use Throwable;

use Spudifull\PhpWorkflowEngine\Application\DTO\ActivityRequest;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowStarted;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Workflow\WorkflowContextInterface;

final class WorkflowContext implements WorkflowContextInterface
{
    private Iterator $historyIterator;

    public function __construct(EventStream $history)
    {
        $this->historyIterator = $history->getIterator();
        $this->historyIterator->rewind();

        $first = $this->historyIterator->current();
        if ($first instanceof WorkflowStarted) {
            $this->historyIterator->next();
        }
    }

    /**
     * @param string $activityClass
     * @param array $args
     *
     * @return mixed
     *
     * @throws Throwable
     */
    public function executeActivity(string $activityClass, array $args = []): mixed
    {
        if ($this->historyIterator->valid()) {
            $currentEvent = $this->historyIterator->current();

            if ($currentEvent instanceof ActivityCompleted) {
                if ($currentEvent->activityName !== $activityClass) {
                    throw new NonDeterministicWorkflowException(
                        "History mismatch: expected $activityClass, got {$currentEvent->activityName}"
                    );
                }

                $this->historyIterator->next();
                return $currentEvent->result;
            }
        }

        return Fiber::suspend(new ActivityRequest($activityClass, $args));
    }
}