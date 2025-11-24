<?php

namespace Spudifull\PhpWorkflowEngine\Domain\Event;

use DateTimeImmutable;

use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final readonly class ActivityCompleted extends AbstractEvent
{
    public function __construct(
        WorkflowId $id,
        public string $activityName,
        public mixed $result,
        DateTimeImmutable $createdDt = new DateTimeImmutable(),
    ){
        parent::__construct($id, $createdDt);
    }
}