<?php

/*
 * Where to send a one-off payment's funds, with Stripe Connect: the connected account, and optionally how much of the
 * payment, as a fixed amount. For a subscription, use SubscriptionTransferData, which takes a percentage instead.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data-transfer_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;


final readonly class PaymentTransferData implements Arrayable
{
    private string $m_destinationAccountId;
    private ?int $m_amount;


    /**
     * @param string $destinationAccountId - the ID of the connected account to send the funds to, e.g. "acct_123".
     *
     * @param int|null $amount - optionally, how much to send, in the currency's smallest unit (e.g. pennies, so 500 for
     * £5.00). Without one, Stripe sends the whole payment.
     *
     * @throws ExceptionInvalidValue - if the destination isn't an account's ID, or the amount isn't positive.
     */
    public function __construct(string $destinationAccountId, ?int $amount = null)
    {
        if (!str_starts_with($destinationAccountId, 'acct_'))
        {
            throw new ExceptionInvalidValue(
                "Funds can only be sent to a connected account, whose ID starts \"acct_\", not {$destinationAccountId}."
            );
        }

        if ($amount !== null && $amount <= 0)
        {
            throw new ExceptionInvalidValue("The amount to send must be positive, but {$amount} was given.");
        }

        $this->m_destinationAccountId = $destinationAccountId;
        $this->m_amount = $amount;
    }


    /**
     * @return array{destination: string, amount?: int}
     */
    public function toArray(): array
    {
        $arrayForm = ['destination' => $this->m_destinationAccountId];

        if ($this->m_amount !== null)
        {
            $arrayForm['amount'] = $this->m_amount;
        }

        return $arrayForm;
    }
}
