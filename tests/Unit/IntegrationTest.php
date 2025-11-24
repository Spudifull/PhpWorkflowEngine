<?php

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowEngine;
use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowExecutor;
use Spudifull\PhpWorkflowEngine\Application\Runtime\WorkflowRunner;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityScheduled;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Workflow\WorkflowContextInterface;
use Spudifull\PhpWorkflowEngine\Infrastructure\Persistence\InMemory\InMemoryEventStore;

class PaymentSaga
{
    public function run(WorkflowContextInterface $ctx, array $input)
    {
        $result = $ctx->executeActivity('ChargeCreditCard', ['amount' => $input['amount']]);

        return "Payment processed: " . $result;
    }
}

/**
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
test('full workflow lifecycle', function () {
    $store = new InMemoryEventStore();
    $engine = new WorkflowEngine($store);

    $container = new class implements ContainerInterface {
        public function get(string $id): object
        {
            if (!class_exists($id)) {
                throw new class extends \Exception implements NotFoundExceptionInterface {};
            }
            return new $id();
        }

        public function has(string $id): bool
        {
            return class_exists($id);
        }
    };

    $executor = new WorkflowExecutor($store, new WorkflowRunner(), $container);

    $id = $engine->start(PaymentSaga::class, ['amount' => 500]);

    $executor->run($id);

    $history = $store->load($id);
    $events = iterator_to_array($history);
    $lastEvent = end($events);

    expect($lastEvent)->toBeInstanceOf(ActivityScheduled::class)
        ->and($lastEvent->activityName)->toBe('ChargeCreditCard');

    $completionEvent = new ActivityCompleted($id, 'ChargeCreditCard', 'SUCCESS_TX_999');
    $store->append($id, new EventStream([$completionEvent]));

    $executor->run($id);

    $history = $store->load($id);
    $events = iterator_to_array($history);

    var_dump('All events:', array_map(fn($e) => get_class($e), $events));

    $lastEvent = end($events);

    expect($lastEvent)->toBeInstanceOf(WorkflowCompleted::class)
        ->and($lastEvent->result)->toBe('Payment processed: SUCCESS_TX_999');
});
