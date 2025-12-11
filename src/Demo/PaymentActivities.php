<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Demo;

class PaymentActivities implements PaymentActivitiesInterface
{
    public function chargeCreditCard(array $input): string
    {
        echo "Charging credit card for amount: {$input['amount']}...\n";
        sleep(1);
        return "TX_" . uniqid();
    }

    public function refundPayment(array $input): void
    {
        echo "Refunding payment for amount: {$input['amount']}...\n";
        sleep(1);
    }
}
