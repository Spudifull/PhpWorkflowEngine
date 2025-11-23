<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;
use Symfony\Component\Uid\Uuid;

readonly class WorkflowId implements Stringable
{
    public function __construct(
        public string $value
    ) {}

    /**
     * @return self
     */
    public static function generate(): self
    {
        return new self(Uuid::v7()->toRfc4122());
    }

    /**
     * @param string $id
     * @return self
     */
    public static function fromString(string $id): self
    {
        if (!Uuid::isValid($id)) {
            throw new InvalidArgumentException("Invalid Workflow ID: $id");
        }
        return new self($id);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
       return $this->value;
    }

    /**
     * @param WorkflowId $other
     * @return bool
     */
    public function equals(WorkflowId $other): bool
    {
        return $this->value === $other->value;
    }
}