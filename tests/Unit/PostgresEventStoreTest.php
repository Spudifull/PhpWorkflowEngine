<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Spudifull\PhpWorkflowEngine\Domain\Contract\EventSerializerInterface;
use Spudifull\PhpWorkflowEngine\Domain\Exceptions\ConcurrencyException;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\Event\WorkflowStarted;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;
use Spudifull\PhpWorkflowEngine\Infrastructure\Persistence\PostgresSql\PostgresEventStore;

/**
 * @throws Throwable
 * @throws Exception
 */
test('it throws ConcurrencyException on duplicate version', function () {
    $connection = Mockery::mock(Connection::class);
    $serializer = Mockery::mock(EventSerializerInterface::class);

    $id = WorkflowId::generate();
    $event = new WorkflowStarted($id, 'TestWorkflow');
    $stream = new EventStream([$event]);

    $serializer->shouldReceive('serialize')->andReturn('{}');

    $connection->shouldReceive('beginTransaction')->once();
    $connection->shouldReceive('rollBack')->once();
    $connection->shouldReceive('quoteSingleIdentifier')->andReturn('"event_store"');
    $connection->shouldReceive('fetchOne')->andReturn(0);

    $driverException = new class extends \Exception implements \Doctrine\DBAL\Driver\Exception {
        public function getSQLState(): ?string
        {
            return '23000';
        }
    };

    $dummyException = new UniqueConstraintViolationException(
        $driverException,
        null
    );
    $connection->shouldReceive('executeStatement')
        ->andThrow($dummyException);

    $store = new PostgresEventStore($connection, $serializer);

    expect(fn() => $store->append($id, $stream))
        ->toThrow(ConcurrencyException::class);
});