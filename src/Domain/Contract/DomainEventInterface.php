<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Contract;

use DateTimeImmutable;

use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

interface DomainEventInterface
{
    public function getWorkflowId(): WorkflowId;
    public function getOccurredDt(): DateTimeImmutable;
    public function getName(): string;
}