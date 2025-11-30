<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowEngine;
use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowExecutor;
use Spudifull\PhpWorkflowEngine\Demo\PaymentSaga;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Repository\EventStoreInterface;

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    new Dotenv()->load(__DIR__ . '/.env');
}

$container = require __DIR__ . '/src/bootstrap.php';

echo "Booting Workflow Engine...\n";

$engine = $container->get(WorkflowEngine::class);
$executor = $container->get(WorkflowExecutor::class);
$store = $container->get(EventStoreInterface::class);

echo "\n--- Phase 1: Client starts workflow ---\n";
$workflowId = $engine->start(PaymentSaga::class, ['amount' => 5000]);

echo "Workflow Started! ID: " . (string)$workflowId . "\n";
echo "(Event 'WorkflowStarted' saved to PostgreSQL)\n";

echo "\n--- Phase 2: Worker picks up the task ---\n";
$executor->run($workflowId);

echo "Workflow Suspended (Waiting for Activity)\n";
echo "(Event 'ActivityScheduled' saved to PostgreSQL)\n";

echo "\n--- Phase 3: External Activity completes ---\n";
echo "(Simulating that 'ChargeCreditCard' was successful...)\n";

$completionEvent = new ActivityCompleted(
    $workflowId,
    'ChargeCreditCard',
    'TX_999_SUCCESS'
);

$store->append($workflowId, new EventStream([$completionEvent]));
echo "Activity Result saved to DB.\n";

echo "\n--- Phase 4: Worker picks up again (REPLAY) ---\n";
$executor->run($workflowId);

echo "Workflow Finished successfully!\n";

$history = $store->load($workflowId);
echo "\nTotal events in history: " . $history->count() . "\n";