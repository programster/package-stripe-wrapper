<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Currency;
use Programster\Stripe\Interfaces\Arrayable;

readonly class TransferData implements Arrayable
{
    /**
     * The parameters used to automatically create a Transfer when the payment succeeds.
     * For more information see https://docs.stripe.com/connect/charges
     * Docs for this object: https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data-transfer_data
     * @param string $stripeAccountId - If specified, successful charges will be attributed to the destination account
     * for tax reporting, and the funds from charges will be transferred to the destination account. The ID of the
     * resulting transfer will be returned on the successful charge’s transfer field.
     * @param int $amount - The amount that will be transferred automatically when a charge succeeds. Don't forget that
     * this is in pennies/cents, not Pounds/Dollars.
     */
    public function __construct(
        private string $stripeAccountId,
        private int $amount_percent,
    )
    {

    }

    public function toArray(): array
    {
        return [
            'destination' => $this->stripeAccountId,
            'amount' => $this->amount,
        ];
    }
}