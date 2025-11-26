<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Exceptions;

use RuntimeException;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final class ConcurrencyException extends RuntimeException
{
    public static function forWorkflow(WorkflowId $id, int $expectedVersion): self
    {
        return new self(sprintf(
            'Concurrency conflict for Workflow %s. Optimistic lock failed at version %d.',
            $id,
            $expectedVersion
        ));
    }
}