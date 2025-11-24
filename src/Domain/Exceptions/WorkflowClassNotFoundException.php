<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Exceptions;

use DomainException;

final class WorkflowClassNotFoundException extends DomainException
{
    public static function for(string $class): self
    {
        return new self("Workflow class not found: {$class}");
    }

    public static function missingRunMethod(string $class): self
    {
        return new self("Workflow {$class} must have run() method");
    }
}