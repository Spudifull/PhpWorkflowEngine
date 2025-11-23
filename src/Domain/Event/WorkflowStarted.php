<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Event;

use DateTimeImmutable;

use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final readonly class WorkflowStarted extends AbstractEvent
{
    public function __construct(
        WorkflowId $workflowId,
        public string $workflowName,
        public array $input = [],
        DateTimeImmutable $occurredDt = new DateTimeImmutable(),
    ) {
        parent::__construct($workflowId, $occurredDt);
    }

    public function getWorkflowId(): WorkflowId
    {
        // TODO: Implement getWorkflowId() method.
    }

    public function getOccurredDt(): DateTimeImmutable
    {
        // TODO: Implement getOccurredDt() method.
    }
}