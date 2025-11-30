<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\Runtime;

use Spudifull\PhpWorkflowEngine\Domain\Workflow\WorkflowContextInterface;

final class ActivityProxy
{
    public function __construct(
        private WorkflowContextInterface $context,
        private string $interfaceName
    ) { }

    /**
     * @param string $name
     * @param array $arguments
     */
    public function __call(string $name, array $arguments)
    {
        $activityName = ucfirst($name);

        $payload = match (count($arguments)) {
            0 => [],
            1 => is_array($arguments[0]) ? $arguments[0] : ['value' => $arguments[0]],
            default => ['args' => $arguments],
        };

        return $this->context->executeActivity($activityName, $payload);
    }
}