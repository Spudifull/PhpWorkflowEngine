<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Event;

use DateTimeImmutable;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final readonly class WorkflowFailed extends AbstractEvent
{
    public function __construct(
        WorkflowId $workflowId,
        public string $error,
        public string $stackTrace,
        DateTimeImmutable $occurredDt = new DateTimeImmutable()
    ) {
        parent::__construct($workflowId, $occurredDt);
    }
}