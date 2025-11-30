<?php

declare(strict_types=1);

namespace Spudifull\PhpWorkflowEngine\Demo;

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

        $chargeResult = $activities->chargeCreditCard(['amount' => $input['amount']]);

        return "Process finished. Charge result: " . $chargeResult;
    }
}
