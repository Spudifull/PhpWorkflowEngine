<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityCompleted;
use Spudifull\PhpWorkflowEngine\Domain\Event\ActivityFailed;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Repository\EventStoreInterface;
use Spudifull\PhpWorkflowEngine\Domain\Repository\QueueInterface;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    new Dotenv()->load(__DIR__ . '/../.env');
}

$idString = $argv[1] ?? null;
$status = $argv[2] ?? 'success';

if (!$idString) {
    die("Usage: php bin/webhook.php <workflow_id> [success|fail]\n");
}

$container = require __DIR__ . '/../src/bootstrap.php';
$store = $container->get(EventStoreInterface::class);
$queue = $container->get(QueueInterface::class);
$id = new WorkflowId($idString);

echo "Webhook received for Workflow: $id\n";

if ($status === 'fail') {
    $event = new ActivityFailed($id, 'ChargeCreditCard', 'Bank Declined Transaction');
    echo "Simulating FAILURE...\n";
} else {
    $event = new ActivityCompleted($id, 'ChargeCreditCard', 'TX_SUCCESS_12345');
    echo "Simulating SUCCESS...\n";
}

$store->append($id, new EventStream([$event]));

echo "Notifying Worker to resume...\n";
$queue->push($id);

echo "Done.\n";
