<?php

/*
 * The parameters used to automatically create a Transfer to another account when a payment succeeds.
 * https://docs.stripe.com/connect/charges
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;


final readonly class TransferData implements Arrayable
{
    private string $m_stripeAccountId;
    private int $m_amount;


    /**
     * The parameters used to automatically create a Transfer when the payment succeeds.
     * For more information see https://docs.stripe.com/connect/charges
     * Docs for this object:
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data-transfer_data
     *
     * @param string $stripeAccountId - If specified, successful charges will be attributed to the destination account
     * for tax reporting, and the funds from charges will be transferred to the destination account. The ID of the
     * resulting transfer will be returned on the successful charge’s transfer field.
     *
     * @param int $amount_percent - What is transferred automatically when a charge succeeds. For a one-time payment
     * (PaymentIntentData), this is a fixed amount in pennies/cents, not Pounds/Dollars. For a subscription
     * (SubscriptionData, or StripeClient::createSubscription()), it is the percentage of each invoice instead.
     * The parameter keeps this name so that existing code that names it keeps working.
     */
    public function __construct(string $stripeAccountId, int $amount_percent)
    {
        $this->m_stripeAccountId = $stripeAccountId;
        $this->m_amount = $amount_percent;
    }


    /**
     * The form for a payment intent, where the amount is a fixed amount in pennies/cents.
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        return [
            'destination' => $this->m_stripeAccountId,
            'amount' => $this->m_amount,
        ];
    }


    /**
     * The form for a subscription, where Stripe takes the amount as a percentage of each invoice instead.
     * @return array<string, mixed>
     */
    public function toSubscriptionArray() : array
    {
        return [
            'destination' => $this->m_stripeAccountId,
            'amount_percent' => $this->m_amount,
        ];
    }
}
