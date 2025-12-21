<?php

declare(strict_types=1);

use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowEngine;
use Spudifull\PhpWorkflowEngine\Demo\PaymentSaga;
use Spudifull\PhpWorkflowEngine\Domain\Repository\QueueInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    new Dotenv()->load(__DIR__ . '/../.env');
}

/** @var ContainerBuilder $container */
$container = require __DIR__ . '/../src/bootstrap.php';

/** @var WorkflowEngine $engine */
$engine = $container->get(WorkflowEngine::class);

/** @var QueueInterface $queue */
$queue = $container->get(QueueInterface::class);

echo "Client: Starting PaymentSaga...\n";
$id = $engine->start(PaymentSaga::class, ['amount' => 5000]);

echo "Client: Pushing task to RabbitMQ...\n";

$queue->push(
    message: $id,
    queue: 'workflow_tasks'
);

echo "Done! Workflow ID: $id\n";
echo "Now check your Worker terminal!\n";
