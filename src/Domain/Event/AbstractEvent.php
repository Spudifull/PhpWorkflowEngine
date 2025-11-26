<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Event;

use DateTimeImmutable;
use ReflectionClass;

use Spudifull\PhpWorkflowEngine\Domain\Contract\DomainEventInterface;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

abstract readonly class AbstractEvent implements DomainEventInterface
{
    public function __construct(
        public WorkflowId $workflowId,
        public DateTimeImmutable $occurredDt = new DateTimeImmutable(),
    ) {}

    /**
     * @return string
     */
    public function getName(): string
    {
        return new ReflectionClass($this)->getShortName();
    }
}