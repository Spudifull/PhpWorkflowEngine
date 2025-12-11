<?php

namespace Spudifull\PhpWorkflowEngine\Application\Runtime;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Spudifull\PhpWorkflowEngine\Domain\Attribute\ActivityInterface;

final class ActivityRegistry
{
    /** @var array<string, callable> */
    private array $handlers = [];

    /**
     * @param object $activity
     */
    public function register(object $activity): void
    {
        $reflection = new ReflectionClass($activity);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(ActivityInterface::class);

            if (empty($attributes)) {
                continue;
            }

            /** @var ActivityInterface $activityInstance */
            $activityInstance = $attributes[0]->newInstance();

            $this->handlers[$activityInstance->prefix] = [$activity, $method->getName()];
        }
    }

    /**
     * @param string $activityName
     * @return callable
     */
    public function getHandler(string $activityName): callable
    {
        if (!isset($this->handlers[$activityName])) {
            throw new RuntimeException("Activity not found: $activityName");
        }

        return $this->handlers[$activityName];
    }
}