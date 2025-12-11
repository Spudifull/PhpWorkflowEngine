<?php

declare(strict_types=1);

use Spudifull\PhpWorkflowEngine\Application\Runtime\ActivityRegistry;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityFailed;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Repository\EventStoreInterface;
use Spudifull\PhpWorkflowEngine\Domain\Repository\QueueInterface;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\ActivityTask;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    new Dotenv()->load(__DIR__ . '/../.env');
}

$container = require __DIR__ . '/../src/bootstrap.php';

echo "Activity Worker started. Waiting for heavy lifting...\n";

/** @var QueueInterface $queue */
$queue = $container->get(QueueInterface::class);

/** @var ActivityRegistry $activityRegistry */
$activityRegistry = $container->get(ActivityRegistry::class);

/** @var EventStoreInterface $eventStore */
$eventStore = $container->get(EventStoreInterface::class);

$queue->consume(function (string $json) use ($activityRegistry, $eventStore, $queue) {
    $activityTask = ActivityTask::fromJson($json);

    echo "Executing Activity: $activityTask->activityName for Workflow: $activityTask->workflowId\n";

    try {
        $task = ActivityTask::fromJson($json);

        $handler = $activityRegistry->getHandler($activityTask->activityName);

        $result = $activityRegistry->execute($task->activityName, $task->args);

        $event = new ActivityCompleted($activityTask->workflowId, $activityTask->activityName, $result);
        $eventStore->append($activityTask->workflowId, new EventStream([$event]));

        echo "Activity Completed. Result: $result\n";

    } catch (Throwable $e) {
        $event = new ActivityFailed($activityTask->workflowId, $activityTask->activityName, $e->getMessage());
        $eventStore->append($activityTask->workflowId, new EventStream([$event]));

        echo "Activity Failed: " . $e->getMessage() . "\n";
    }

    $queue->push($activityTask->workflowId, 'workflow_tasks');

    public function execute(string $activityName, array $args): mixed
    {
        $handler = $this->getHandler($activityName);

        return $handler($args);
    }

}, 'activity_tasks');
