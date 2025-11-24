<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Event;

use DateTimeImmutable;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final readonly class ActivityScheduled extends AbstractEvent
{
    public function __construct(
        WorkflowId $workflowId,
        public string $activityName,
        public array $args,
        DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {
        parent::__construct($workflowId, $occurredAt);
    }
}
