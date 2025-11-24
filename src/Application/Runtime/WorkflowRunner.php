<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\Runtime;

use Fiber;
use RuntimeException;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Throwable;

final class WorkflowRunner
{
    /**
     * @param object $workflow
     * @param string $method
     * @param EventStream $history
     * @param array $args
     * @return mixed
     */
    public function run(object $workflow, string $method, EventStream $history, array $args = []): mixed
    {
        $context = new WorkflowContext($history);

        $fiber = new Fiber(function () use ($workflow, $method, $context, $args) {
            return $workflow->$method($context, $args);
        });

        try {
            $output = $fiber->start();

            if ($fiber->isSuspended()) {
                return $output;
            }

            return $fiber->getReturn();

        } catch (Throwable $exception) {
            throw new RuntimeException("Error inside workflow: " . $exception->getMessage(), 0, $exception);
        }
    }
}