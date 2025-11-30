<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Event;

use DateTimeImmutable;

use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final readonly class ActivityCompleted extends AbstractEvent
{
    public function __construct(
        WorkflowId $workflowId,
        public string $activityName,
        public mixed $result,
        DateTimeImmutable $occurredDt = new DateTimeImmutable(),
    ){
        parent::__construct($workflowId, $occurredDt);
    }
}