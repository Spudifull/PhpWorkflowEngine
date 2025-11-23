<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Application\DTO;

final readonly class ActivityRequest
{
    public function __construct(
        public string $name,
        public array $args,
    ) {}
}