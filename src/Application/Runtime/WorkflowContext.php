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

    public function __construct(
        private HistoryReplayer $replayer
    ) {}

    /**
     * @param string $activityClass
     * @param array $args
     * @return mixed
     * @throws Throwable
     */
    public function executeActivity(string $activityClass, array $args = []): mixed
    {
        return $this->replayer->handleActivity($activityClass, $args);
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