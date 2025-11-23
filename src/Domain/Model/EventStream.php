<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Model;

use ArrayIterator;
use Countable;
use IteratorAggregate;

use Spudifull\PhpWorkflowEngine\Domain\Contract\DomainEventInterface;

final readonly class EventStream implements IteratorAggregate, Countable
{
    private array $events;

    public function __construct(array $events = [])
    {
        foreach ($events as $event) {
            if (!$event instanceof DomainEventInterface) {
                throw new \InvalidArgumentException('EventStream must contain only DomainEventInterface objects');
            }
        }
        $this->events = array_values($events);
    }

    /**
     * @return self
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param DomainEventInterface $event
     * @return self
     */
    public function add(DomainEventInterface $event): self
    {
        return new self([...$this->events, $event]);
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->events);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->events);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->events);
    }
}