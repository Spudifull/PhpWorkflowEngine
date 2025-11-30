<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\Runtime;

use Fiber;
use InvalidArgumentException;
use Iterator;
use ReflectionException;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityFailed;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\ActivityFailureException;
use Throwable;

use Spudifull\PhpWorkflowEngine\Domain\Attribute\ActivityInterface;
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

            if (!$event instanceof ActivityCompleted && !$event instanceof ActivityFailed) {
                $this->historyIterator->next();
                continue;
            }

            if ($event->activityName !== $activityClass) {
                throw NonDeterministicWorkflowException::activityMismatch(
                    expected: $activityClass,
                    actual: $event->activityName
                );
            }

            $this->historyIterator->next();

            if ($event instanceof ActivityCompleted) {
                return $event->result;
            }

            throw new ActivityFailureException($event->errorMessage);
        }

        return Fiber::suspend(new ActivityRequest($activityClass, $args));
    }

    /**
     * @template T
     * @param class-string<T> $interfaceClass
     * @return T
     * @throws ReflectionException
     */
    public function newActivityStub(string $interfaceClass): mixed
    {
        $reflection = new \ReflectionClass($interfaceClass);
        $attributes = $reflection->getAttributes(ActivityInterface::class);

        if (empty($attributes)) {
            throw new InvalidArgumentException("Interface {$interfaceClass} must have #[ActivityInterface] attribute");
        }

        return new ActivityProxy($this, $interfaceClass);
    }
}