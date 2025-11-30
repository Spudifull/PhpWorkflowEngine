<?php

namespace Spudifull\PhpWorkflowEngine\Demo;

use Spudifull\PhpWorkflowEngine\Domain\Attribute\ActivityInterface;

#[ActivityInterface]
interface PaymentActivitiesInterface
{
    public function chargeCreditCard(array $input): string;

    public function refundPayment(array $input): void;
}