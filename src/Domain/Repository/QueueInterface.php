<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Repository;

use Stringable;

use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

interface QueueInterface
{
    /**
     * Отправить сообщение в очередь
     *
     * @param Stringable $message Объект с __toString() (WorkflowId, ActivityTask, etc.)
     * @param string|null $queue Имя очереди (по умолчанию 'workflow_tasks')
     */
    public function push(Stringable $message, ?string $queue = null): void;

    /**
     * Получить одно сообщение из очереди
     *
     * @param string|null $queue Имя очереди
     * @return string|null JSON строка или null если очередь пуста
     */
    public function pop(?string $queue = null): ?string;

    /**
     * Слушать очередь и вызывать callback для каждого сообщения
     *
     * @param callable(string): void $callback Обработчик сообщения (принимает JSON строку)
     * @param string|null $queue Имя очереди
     */
    public function consume(callable $callback, ?string $queue = null): void;
}
