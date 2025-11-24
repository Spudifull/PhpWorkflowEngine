<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Event;

use DateTimeImmutable;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final readonly class WorkflowCompleted extends AbstractEvent
{
    public function __construct(
        WorkflowId $workflowId,
        public mixed $result,
        DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {
        parent::__construct($workflowId, $occurredAt);
    }
}
