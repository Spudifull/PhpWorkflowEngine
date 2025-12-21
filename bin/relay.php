<?php

use Doctrine\DBAL\Connection;
use Spudifull\PhpWorkflowEngine\Domain\Repository\QueueInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    new Dotenv()->load(__DIR__ . '/../.env');
}

/** @var ContainerBuilder $container */
$container = require __DIR__ . '/../src/bootstrap.php';

echo "Relay Service started...\n";

/** @var Connection $connection */
$connection = $container->get(Connection::class);

/** @var QueueInterface $queue */
$queue = $container->get(QueueInterface::class);

while (true) {
    try {
        $messages = $connection->fetchAllAssociative(
                "select id, queue_name, payload from outbox where processed_dt is null order by id asc limit 10"
        );

        foreach ($messages as $message) {
            $queue->push($message['payload'], $message['queue_name']);

            $connection->executeStatement(
                    "UPDATE outbox SET processed_dt = NOW() WHERE id = ?",
                    [$message['id']]
            );

            echo "OK\n";
        }
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}