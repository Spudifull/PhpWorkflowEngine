<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Infrastructure\Transport;

use ErrorException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use RuntimeException;
use Throwable;

use Spudifull\PhpWorkflowEngine\Domain\Repository\QueueInterface;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

final class RabbitMQueue implements QueueInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;
    private bool $shouldStop = false;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $pass,
        private readonly string $queueName = 'workflow_tasks',
        private readonly int $maxRetries = 3,
        private readonly float $connectionTimeout = 5.0,
        private readonly int $heartbeat = 30,
    ){}

    /**
     * @param WorkflowId $id
     * @return void
     */
    public function push(WorkflowId $id): void
    {
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            try {
                $this->connect();

                $msg = new AMQPMessage(
                    (string)$id,
                    [
                        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                        'timestamp' => time(),
                    ]
                );

                $this->channel->basic_publish($msg, '', $this->queueName);

                return;

            } catch (Throwable $e) {
                $attempts++;
                $this->disconnect();

                if ($attempts >= $this->maxRetries) {
                    throw new RuntimeException(
                        sprintf(
                            'Failed to push workflow %s to queue after %d attempts',
                            $id,
                            $this->maxRetries
                        ),
                        previous: $e
                    );
                }

                usleep(100_000 * $attempts);
            }
        }
    }

    /**
     * @param callable $callback
     * @throws ErrorException
     */
    public function consume(callable $callback): void
    {
        $this->connect();

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, fn() => $this->stop());
            pcntl_signal(SIGINT, fn() => $this->stop());
        }

        $wrapper = function (AMQPMessage $msg) use ($callback): void {
            $workflowId = $msg->getBody();

            try {
                $callback(new WorkflowId($workflowId));
                $msg->ack();
            } catch (\Throwable) {
                $msg->nack();
            }
        };

        $this->channel->basic_qos(
            prefetch_size: 0,
            prefetch_count: 1,
            a_global: false
        );

        $this->channel->basic_consume(
            queue: $this->queueName,
            callback: $wrapper
        );

        while (!$this->shouldStop) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            try {
                $this->channel->wait(null, false, 1);
            } catch (AMQPTimeoutException) {
                continue;
            } catch (\Throwable) {
                break;
            }
        }
    }

    private function connect(): void
    {
        if ($this->connection !== null && $this->connection->isConnected()) {
            return;
        }

        $readWriteTimeout = max(
            $this->heartbeat * 2 + 5,
            65.0
        );

        try {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->pass,
                connection_timeout: $this->connectionTimeout,
                read_write_timeout: $readWriteTimeout,
                heartbeat: $this->heartbeat
            );

            $this->channel = $this->connection->channel();

            $this->channel->queue_declare(
                $this->queueName . '_dlq',
                false,
                true,
                false,
                false
            );

            $this->channel->queue_declare(
                $this->queueName,
                false,
                true,
                false,
                false,
                false,
                new AMQPTable([
                    'x-dead-letter-exchange' => '',
                    'x-dead-letter-routing-key' => $this->queueName . '_dlq',
                ])
            );

        } catch (Throwable $e) {
            $this->disconnect();

            throw new RuntimeException(
                sprintf('Failed to connect to RabbitMQ at %s:%d', $this->host, $this->port),
                previous: $e
            );
        }
    }

    public function stop(): void
    {
        $this->shouldStop = true;
    }

    private function disconnect(): void
    {
        try {
            $this->channel?->close();
        } catch (Throwable) {}

        try {
            $this->connection?->close();
        } catch (Throwable) {}

        $this->channel = null;
        $this->connection = null;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}