<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Exceptions;

use DomainException;

final class NonDeterministicWorkflowException extends DomainException
{
    public static function activityMismatch(string $expected, string $actual): self
    {
        return new self(
            "Non-deterministic workflow: expected activity '$expected', but history shows '$actual'"
        );
    }
}