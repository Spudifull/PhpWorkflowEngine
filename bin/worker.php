<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowExecutor;
use Spudifull\PhpWorkflowEngine\Domain\Repository\QueueInterface;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    new Dotenv()->load(__DIR__ . '/../.env');
}

$container = require __DIR__ . '/../src/bootstrap.php';

echo "Worker started. Waiting for tasks...\n";

/** @var QueueInterface $queue */
$queue = $container->get(QueueInterface::class);

/** @var WorkflowExecutor $executor */
$executor = $container->get(WorkflowExecutor::class);

$queue->consume(function (string $message) use ($executor) {
    $id = WorkflowId::fromString($message);

    echo "Processing Workflow: $id\n";

    $executor->run($id);

    echo "Done processing $id\n";
});
