<?php

use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowStarted;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\WorkflowNotFoundException;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;
use Spudifull\PhpWorkflowEngine\Infrastructure\Persistence\InMemory\InMemoryEventStore;

/**
 * @throws Exception
 */
test( 'it can save and load events', function ()
{
    $store = new InMemoryEventStore();
    $id = WorkflowId::generate();

    $event = new WorkflowStarted(
        workflowId: $id,
        workflowName: 'FirstWorkflow',
        input: ['amount' => 100]
    );

    $stream = new EventStream([$event]);

    $store->append($id, $stream);

    $loadedStream = $store->load($id);

    expect($loadedStream)->toBeInstanceOf(EventStream::class)
        ->and($loadedStream->count())->toBe(1);

    $loadedEvents = iterator_to_array($loadedStream);
    expect($loadedEvents[0])->toBeInstanceOf(WorkflowStarted::class)
        ->and($loadedEvents[0]->workflowName)->toBe('FirstWorkflow');
});

/**
 * @throws Exception
 */
test('it appends new events to existing stream', function () {
    $store = new InMemoryEventStore();
    $id = WorkflowId::generate();

    $store->append($id, new EventStream([
        new WorkflowStarted($id, 'Test', ['key' => 'value1'])
    ]));

    $store->append($id, new EventStream([
        new WorkflowStarted($id, 'AnotherEvent', ['key' => 'value2'])
    ]));

    $loadedStream = $store->load($id);

    expect($loadedStream->count())->toBe(2);
});

/**
 * @throws Exception
 */
test('it throws exception if workflow not found', function () {
    $store = new InMemoryEventStore();
    $id = WorkflowId::generate();

    $store->load($id);
})->throws(WorkflowNotFoundException::class);