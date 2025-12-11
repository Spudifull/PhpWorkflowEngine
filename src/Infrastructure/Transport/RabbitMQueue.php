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
use Stringable;
use Throwable;

use Spudifull\PhpWorkflowEngine\Domain\Repository\QueueInterface;

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
        private readonly string $defaultQueue = 'workflow_tasks',
        private readonly int $maxRetries = 3,
        private readonly float $connectionTimeout = 5.0,
        private readonly int $heartbeat = 30,
    ) {}

    /**
     * @param Stringable $message
     * @param string|null $queue
     */
    public function push(Stringable $message, ?string $queue = null): void
    {
        $queueName = $queue ?? $this->defaultQueue;
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            try {
                $this->connect();
                $this->ensureQueueExists($queueName);

                $msg = new AMQPMessage(
                    (string)$message,
                    [
                        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                        'timestamp' => time(),
                    ]
                );

                $this->channel->basic_publish($msg, '', $queueName);

                return;

            } catch (Throwable $e) {
                $attempts++;
                $this->disconnect();

                if ($attempts >= $this->maxRetries) {
                    throw new RuntimeException(
                        sprintf(
                            'Failed to push message to queue "%s" after %d attempts: %s',
                            $queueName,
                            $this->maxRetries,
                            $message
                        ),
                        previous: $e
                    );
                }

                usleep(100_000 * $attempts);
            }
        }
    }

    /**
     * @param string|null $queue
     * @return string|null
     */
    public function pop(?string $queue = null): ?string
    {
        $queueName = $queue ?? $this->defaultQueue;

        try {
            $this->connect();
            $this->ensureQueueExists($queueName);

            $message = $this->channel->basic_get($queueName, true);

            return $message?->getBody();

        } catch (Throwable $e) {
            throw new RuntimeException(
                sprintf('Failed to pop message from queue "%s"', $queueName),
                previous: $e
            );
        }
    }

    /**
     * @param callable(string): void $callback
     * @param string|null $queue
     * @throws ErrorException
     */
    public function consume(callable $callback, ?string $queue = null): void
    {
        $queueName = $queue ?? $this->defaultQueue;

        $this->connect();
        $this->ensureQueueExists($queueName);

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, fn() => $this->stop());
            pcntl_signal(SIGINT, fn() => $this->stop());
        }

        $wrapper = function (AMQPMessage $msg) use ($callback): void {
            $messageBody = $msg->getBody();

            try {
                $callback($messageBody);
                $msg->ack();
            } catch (Throwable $e) {
                $msg->nack(requeue: true);
            }
        };

        $this->channel->basic_qos(
            prefetch_size: 0,
            prefetch_count: 1,
            a_global: false
        );

        $this->channel->basic_consume(
            queue: $queueName,
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
            } catch (Throwable) {
                break;
            }
        }
    }

    /**
     * @param string $queueName
     */
    private function ensureQueueExists(string $queueName): void
    {
        static $declaredQueues = [];

        if (isset($declaredQueues[$queueName])) {
            return;
        }

        $this->channel->queue_declare(
            $queueName . '_dlq',
            false,
            true,
            false,
            false
        );

        $this->channel->queue_declare(
            $queueName,
            false,
            true,
            false,
            false,
            false,
            new AMQPTable([
                'x-dead-letter-exchange' => '',
                'x-dead-letter-routing-key' => $queueName . '_dlq',
            ])
        );

        $declaredQueues[$queueName] = true;
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