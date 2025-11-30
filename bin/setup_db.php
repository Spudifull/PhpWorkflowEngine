<?php

use Doctrine\DBAL\Connection;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    new Dotenv()->load(__DIR__ . '/../.env');
}

$container = require __DIR__ . '/../src/bootstrap.php';

echo "Connecting to database...\n";

try {
    /** @var Connection $connection */
    $connection = $container->get(Connection::class);

    $sql = <<<SQL
        create table if not exists event_store (
            id bigserial primary key, 
            workflow_id varchar(36) not null, 
            version int not null,
            type varchar(255) not null, 
            data jsonb not null, 
            occurred_at timestamp(6) with time zone not null
        );

        create unique index if not exists event_store_unique_workflow_version
        on event_store (workflow_id, version);

        create index if not exists event_store_workflow_id_idx on event_store (workflow_id);
    SQL;

    echo "Executing migration...\n";
    $connection->executeStatement($sql);

    echo "Done! Table 'event_store' created successfully.\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
