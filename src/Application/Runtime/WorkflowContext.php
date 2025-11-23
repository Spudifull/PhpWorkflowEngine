<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\Runtime;

use Fiber;
use Spudifull\PhpWorkflowEngine\Application\DTO\ActivityRequest;
use Spudifull\PhpWorkflowEngine\Domain\Workflow\WorkflowContextInterface;
use Throwable;

final class WorkflowContext implements WorkflowContextInterface
{
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
        $request = new ActivityRequest($activityClass, $args);

        return Fiber::suspend($request);
    }
}