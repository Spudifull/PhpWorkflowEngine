<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Demo;

use Spudifull\PhpWorkflowEngine\Domain\Exceptions\ActivityFailureException;
use Spudifull\PhpWorkflowEngine\Domain\Workflow\WorkflowContextInterface;

class PaymentSaga
{
    /**
     * @param WorkflowContextInterface $ctx
     * @param array $input
     * @return string
     */
    public function run(WorkflowContextInterface $ctx, array $input): string
    {
        /** @var PaymentActivitiesInterface $activities */
        $activities = $ctx->newActivityStub(PaymentActivitiesInterface::class);

        try {
            $chargeResult = $activities->chargeCreditCard(['amount' => $input['amount']]);

            $activities->chargeCreditCard(['amount' => 9999]);

        } catch (ActivityFailureException $e) {
            $activities->refundPayment(['amount' => $input['amount']]);

            return "Workflow Failed, but Money Refunded! Error: " . $e->getMessage();
        }

        return "Success!";
    }
}
