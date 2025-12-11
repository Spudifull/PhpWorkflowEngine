<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Spudifull\PhpWorkflowEngine\Application\Runtime\ActivityRegistry;
use Spudifull\PhpWorkflowEngine\Demo\PaymentActivities;
use Spudifull\PhpWorkflowEngine\Domain\Repository\QueueInterface;
use Spudifull\PhpWorkflowEngine\Infrastructure\Transport\RabbitMQueue;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use Spudifull\PhpWorkflowEngine\Demo\PaymentSaga;
use Spudifull\PhpWorkflowEngine\Application\Runtime\WorkflowRunner;
use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowEngine;
use Spudifull\PhpWorkflowEngine\Application\Service\WorkflowExecutor;
use Spudifull\PhpWorkflowEngine\Domain\Repository\EventStoreInterface;
use Spudifull\PhpWorkflowEngine\Infrastructure\Serializer\EventSerializer;
use Spudifull\PhpWorkflowEngine\Infrastructure\Persistence\PostgresSql\PostgresEventStore;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(Connection::class)
        ->factory([DriverManager::class, 'getConnection'])
        ->args([
            [
                'driver' => 'pdo_pgsql',
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port' => (int)($_ENV['DB_PORT'] ?? 5433),
                'dbname' => $_ENV['DB_NAME'] ?? 'workflow_db',
                'user' => $_ENV['DB_USER'] ?? 'app_user',
                'password' => $_ENV['DB_PASSWORD'] ?? 'secret_password',
                'charset' => 'utf8',
            ]
        ]);

    $services->set(QueueInterface::class, RabbitMQueue::class)
        ->args([
            '$host' => $_ENV['RABBITMQ_HOST'] ?? '127.0.0.1',
            '$port' => (int)($_ENV['RABBITMQ_PORT'] ?? 5672),
            '$user' => $_ENV['RABBITMQ_USER'] ?? 'guest',
            '$pass' => $_ENV['RABBITMQ_PASS'] ?? 'guest',
        ]);

    $services->set(EventSerializer::class);

    $services->set(EventStoreInterface::class, PostgresEventStore::class)
        ->args([
            service(Connection::class),
            service(EventSerializer::class)
        ]);

    $services->set(WorkflowRunner::class);

    $services->set(WorkflowEngine::class)
        ->args([service(EventStoreInterface::class)]);

    $services->set(WorkflowExecutor::class)
        ->args([
            service(EventStoreInterface::class),
            service(WorkflowRunner::class),
            service('service_container')
        ]);

    $services->set(PaymentActivities::class);

    $services->set(ActivityRegistry::class)
        ->call('register', [service(PaymentActivities::class)]);

    $services->set(PaymentSaga::class)->public();
};
