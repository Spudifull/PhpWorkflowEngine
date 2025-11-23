<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Repository;

use Exception;

use Spudifull\PhpWorkflowEngine\Domain\Exceptions\WorkflowNotFoundException;
use Spudifull\PhpWorkflowEngine\Domain\Model\EventStream;
use Spudifull\PhpWorkflowEngine\Domain\ValueObject\WorkflowId;

interface EventStoreInterface
{
    /**
     * Добавляет новые события в конец потока.
     * @var WorkflowId $id
     * @var EventStream $events
     * @throws Exception Если возникла ошибка конкуренции (Optimistic Lock)
     */
    public function append(WorkflowId $id, EventStream $events): void;

    /**
     * Загружает ВСЮ историю событий для восстановления состояния.
     * @var WorkflowId $id
     * @throws WorkflowNotFoundException
     */
    public function load(WorkflowId $id): EventStream;

    /**
     * Проверяет, существует ли такой workflow
     * @var WorkflowId $id
     */
    public function has(WorkflowId $id): bool;
}