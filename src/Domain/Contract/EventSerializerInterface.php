<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Domain\Contract;

interface EventSerializerInterface
{
    public function serialize(DomainEventInterface $event): string;
    public function deserialize(string $data): DomainEventInterface;
}