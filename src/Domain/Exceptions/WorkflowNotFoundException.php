<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Exceptions;

use RuntimeException;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final class WorkflowNotFoundException extends RuntimeException
{
    public static function withId(WorkflowId $id): self
    {
        return new self(sprintf("Workflow execution with ID '%s' not found.", $id));
    }
}