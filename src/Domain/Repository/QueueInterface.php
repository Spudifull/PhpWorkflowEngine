<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Repository;

use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

interface QueueInterface
{
    /**
     * Отправить задачу на выполнение workflow (или продолжение выполнения).
     */
    public function push(WorkflowId $id): void;

    /**
     * Слушать очередь и вызывать callback для каждого сообщения.
     * @param callable(WorkflowId): void $callback
     */
    public function consume(callable $callback): void;
}
