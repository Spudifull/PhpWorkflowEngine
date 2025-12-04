<?php

use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowEngine;
use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowExecutor;
use Spudifull\PhpWorkflowEngine\Application\Runtime\WorkflowRunner;
use Spudifull\PhpWorkflowEngine\Domain\Attribute\ActivityInterface;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityFailed;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityScheduled;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\ActivityFailureException;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Workflow\WorkflowContextInterface;
use Spudifull\PhpWorkflowEngine\Infrastructure\Persistence\InMemory\InMemoryEventStore;
use Psr\Container\ContainerInterface;

#[ActivityInterface]
interface TestSagaActivities {
    public function reserve(array $input): string;
    public function charge(array $input): string;
    public function cancelReservation(array $input): void;
}

class TestSagaWorkflow {
    public function run(WorkflowContextInterface $ctx, array $input) {
        /** @var TestSagaActivities $activities */
        $activities = $ctx->newActivityStub(TestSagaActivities::class);

        $activities->reserve($input);

        try {
            $activities->charge($input);
        } catch (ActivityFailureException $e) {
            $activities->cancelReservation($input);
            return 'Compensated';
        }

        return 'Success';
    }
}

test('it executes compensation when activity fails', function () {
    $store = new InMemoryEventStore();
    $engine = new WorkflowEngine($store);

    $container = new class implements ContainerInterface {
        public function get(string $id): object { return new $id(); }
        public function has(string $id): bool { return true; }
    };

    $executor = new WorkflowExecutor($store, new WorkflowRunner(), $container);

    $id = $engine->start(TestSagaWorkflow::class, ['item' => 'laptop']);

    $executor->run($id);

    $store->append($id, new EventStream([
        new ActivityCompleted($id, 'Reserve', 'OK_ID_123')
    ]));

    $executor->run($id);

    $store->append($id, new EventStream([
        new ActivityFailed($id, 'Charge', 'Insufficient Funds')
    ]));

    $executor->run($id);

    $history = $store->load($id);
    $events = iterator_to_array($history);
    $lastEvent = end($events);

    expect($lastEvent)->toBeInstanceOf(ActivityScheduled::class)
        ->and($lastEvent->activityName)->toBe('CancelReservation')
        ->and($lastEvent->args)->toBe(['item' => 'laptop']);
});
