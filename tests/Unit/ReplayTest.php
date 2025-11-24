<?php

use Spudifull\PhpWorkflowEngine\Application\Runtime\WorkflowRunner;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowStarted;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;
use Spudifull\PhpWorkflowEngine\Domain\Workflow\WorkflowContextInterface;

class ReplayWorkflow
{
    public function run(WorkflowContextInterface $ctx)
    {
        $paymentId = $ctx->executeActivity('PaymentActivity', ['amount' => 100]);

        $ctx->executeActivity('DeliveryActivity', ['order_id' => $paymentId]);
    }
}

test('it replays completed activities without suspending', function () {
    $runner = new WorkflowRunner();
    $workflow = new ReplayWorkflow();
    $id = WorkflowId::generate();

    $history = new EventStream([
        new WorkflowStarted($id, 'ReplayWorkflow'),
        new ActivityCompleted($id, 'PaymentActivity', 'PAY-123')
    ]);

    $result = $runner->run($workflow, 'run', $history);

    expect($result)->not->toBeNull()
        ->and($result->name)->toBe('DeliveryActivity')
        ->and($result->args)->toBe(['order_id' => 'PAY-123']);
});