<?php

use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowEngine;
use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowExecutor;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityFailed;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Repository\EventStoreInterface;
use Spudifull\PhpWorkflowEngine\Demo\PaymentSaga;

use Symfony\Component\Dotenv\Dotenv;

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
$workflowId = $engine->start(PaymentSaga::class, ['amount' => 100]);
echo "Workflow Started! ID: $workflowId\n";

$executor->run($workflowId);

echo "\n--- Phase 3: First Activity Succeeds ---\n";
$store->append($workflowId, new EventStream([
    new ActivityCompleted($workflowId, 'ChargeCreditCard', 'OK_100')
]));

$executor->run($workflowId);

echo "\n--- Phase 5: Second Activity FAILS! ---\n";
$store->append($workflowId, new EventStream([
    new ActivityFailed($workflowId, 'ChargeCreditCard', 'Service Unavailable')
]));
echo "Activity Failed Event saved.\n";

echo "\n--- Phase 6: Worker handles Failure (Compensating) ---\n";
$executor->run($workflowId);

echo "\n--- Phase 7: Simulating Refund Success ---\n";
$store->append($workflowId, new EventStream([
    new ActivityCompleted($workflowId, 'RefundPayment', 'REFUND_OK')
]));

$executor->run($workflowId);

echo "\nWorkflow Finished properly with Compensation!\n";

$history = $store->load($workflowId);
$lastEvent = iterator_to_array($history)[count($history)-1];
var_dump($lastEvent->result ?? 'No result');
