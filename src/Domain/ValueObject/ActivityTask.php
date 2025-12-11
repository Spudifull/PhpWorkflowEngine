<?php

namespace Spudifull\PhpWorkflowEngine\Domain\ValueObject;

use InvalidArgumentException;
use JsonException;
use Stringable;

final readonly class ActivityTask implements Stringable
{
    public function __construct(
        public WorkflowId $workflowId,
        public string $activityName,
        public array $args,
    ) {}

    /**
     * @return string
     */
    public function __toString(): string
    {
        $json = json_encode([
            'workflow_id' => (string)$this->workflowId,
            'activity_name' => $this->activityName,
            'args' => $this->args,
        ]);

        return $json !== false ? $json : '{}';
    }

    /**
     * @param string $json
     * @return ActivityTask
     * @throws JsonException
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($data['workflow_id'], $data['activity_name'], $data['args'])) {
            throw new InvalidArgumentException('Invalid ActivityTask JSON structure');
        }

        return new self(
            WorkflowId::fromString($data['workflow_id']),
            $data['activity_name'],
            $data['args'],
        );
    }
}