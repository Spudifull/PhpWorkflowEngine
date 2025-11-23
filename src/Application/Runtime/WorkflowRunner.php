<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\Runtime;

use Fiber;
use RuntimeException;
use Throwable;

use Spudifull\PhpWorkflowEngine\Application\DTO\ActivityRequest;

final class WorkflowRunner
{
    public function run(object $workflow, string $method, array $args = []): ?ActivityRequest
    {
        $context = new WorkflowContext();

        $fiber = new Fiber(function () use ($workflow, $method, $context, $args) {
            return $workflow->$method($context, ...$args);
        });

        try {
            $output = $fiber->start();
        } catch (Throwable $exception) {
            throw new RuntimeException("Error inside workflow: " . $exception->getMessage(), 0, $exception);
        }

        if ($output instanceof ActivityRequest) {
            return $output;
        }

        return null;
    }
}