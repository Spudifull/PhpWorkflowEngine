<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\Runtime;

use Fiber;
use Iterator;
use Spudifull\PhpWorkflowEngine\Application\DTO\ActivityRequest;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityFailed;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\ActivityFailureException;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\NonDeterministicWorkflowException;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Throwable;

final class HistoryReplayer
{
    private Iterator $historyIterator;

    public function __construct(EventStream $history)
    {
        $this->historyIterator = $history->getIterator();
        $this->historyIterator->rewind();
    }

    /**
     * @param string $activityName
     * @param array $args
     * @return mixed
     * @throws Throwable
     */
    public function handleActivity(string $activityName, array $args): mixed
    {
        while ($this->historyIterator->valid()) {
            $event = $this->historyIterator->current();

            if (!$event instanceof ActivityCompleted && !$event instanceof ActivityFailed) {
                $this->historyIterator->next();
                continue;
            }

            if ($event->activityName !== $activityName) {
                throw NonDeterministicWorkflowException::activityMismatch(
                    expected: $activityName,
                    actual: $event->activityName
                );
            }

            $this->historyIterator->next();

            if ($event instanceof ActivityCompleted) {
                return $event->result;
            }

            throw new ActivityFailureException($event->errorMessage);
        }

        return Fiber::suspend(new ActivityRequest($activityName, $args));
    }
}