<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Repository;

interface OutboxRepositoryInterface
{
    public function schedule(string $queueName, string $payload): void;
}