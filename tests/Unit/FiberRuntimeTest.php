<?php

use Spudifull\PhpWorkflowEngine\Application\DTO\ActivityRequest;
use Spudifull\PhpWorkflowEngine\Application\Runtime\WorkflowRunner;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream; // <-- Не забудь импорт
use Spudifull\PhpWorkflowEngine\Domain\Workflow\WorkflowContextInterface;

class TestWorkflowWithActivity
{
    public function run(WorkflowContextInterface $ctx)
    {
        $result = $ctx->executeActivity('SomeActivity', ['foo' => 'bar']);
        return 'Done: ' . $result;
    }
}

test('runner suspends execution when activity is requested', function () {
    $runner = new WorkflowRunner();
    $workflow = new TestWorkflowWithActivity();

    $output = $runner->run($workflow, 'run', EventStream::empty());

    expect($output)->toBeInstanceOf(ActivityRequest::class)
        ->and($output->name)->toBe('SomeActivity')
        ->and($output->args)->toBe(['foo' => 'bar']);
});