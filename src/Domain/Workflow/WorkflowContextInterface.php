<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Workflow;

interface WorkflowContextInterface
{
    /**
     * Запланировать выполнение Activity.
     *
     * @param class-string $activityClass Имя класса Activity
     * @param array $args Аргументы для Activity
     * @return mixed Возвращает результат выполнения (или прерывает выполнение через yield)
     */
    public function executeActivity(string $activityClass, array $args = []): mixed;
}
