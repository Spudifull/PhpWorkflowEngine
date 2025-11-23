<?php

namespace Spudifull\PhpWorkflowEngine\Infrastructure\Persistence\InMemory;

use AppendIterator;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\WorkflowNotFoundException;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Repository\EventStoreInterface;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final class InMemoryEventStore implements EventStoreInterface
{
    /**
     * Хранилище в памяти: [WorkflowId => EventStream]
     * @var array<string, EventStream>
     */
    private array $store = [];

    public function append(WorkflowId $id, EventStream $events): void
    {
        $key = (string)$id;

        if (!isset($this->store[$key])) {
            $this->store[$key] = $events;
            return;
        }

        $merged = new AppendIterator();
        $merged->append($this->store[$key]->getIterator());
        $merged->append($events->getIterator());

        $this->store[$key] = new EventStream(iterator_to_array($merged, false));
    }

    public function load(WorkflowId $id): EventStream
    {
        if (!$this->has($id)) {
            throw WorkflowNotFoundException::withId($id);
        }

        return $this->store[(string)$id];
    }

    public function has(WorkflowId $id): bool
    {
        return isset($this->store[(string)$id]);
    }
}