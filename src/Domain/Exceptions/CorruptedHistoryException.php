<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Exceptions;

use DomainException;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final class CorruptedHistoryException extends DomainException
{
    public static function missingWorkflowStarted(WorkflowId $id): self
    {
        return new self("Corrupted history for workflow {$id}: First event must be WorkflowStarted");
    }
}