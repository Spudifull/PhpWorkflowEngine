<?php

use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowEngine;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowStarted;
use Spudifull\PhpWorkflowEngine\Infrastructure\Persistence\InMemory\InMemoryEventStore;

test('it starts a workflow by saving the initial event', function () {
    $store = new InMemoryEventStore();
    $engine = new WorkflowEngine($store);
    $workflowClass = 'App\\Workflows\\OrderSaga';
    $inputData = ['order_id' => 123];

    $id = $engine->start($workflowClass, $inputData);

    $stream = $store->load($id);

    expect($stream->count())->toBe(1);

    $event = iterator_to_array($stream)[0];
    expect($event)->toBeInstanceOf(WorkflowStarted::class)
        ->and($event->workflowName)->toBe($workflowClass)
        ->and($event->input)->toBe($inputData);
});