<?php

namespace Spudifull\PhpWorkflowEngine\Domain\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class ActivityInterface
{
    public function __construct(
       public ?string $prefix = null
    ) {}
}