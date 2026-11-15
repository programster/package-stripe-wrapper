<?php

/*
 * Where to send a subscription's funds, with Stripe Connect: the connected account, and optionally what percentage of
 * each invoice. For a one-off payment, use PaymentTransferData, which takes a fixed amount instead.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-transfer_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;


final readonly class SubscriptionTransferData implements Arrayable
{
    private string $m_destinationAccountId;
    private ?float $m_amountPercent;


    /**
     * @param string $destinationAccountId - the ID of the connected account to send the funds to, e.g. "acct_123".
     *
     * @param float|null $amountPercent - optionally, the percentage of each invoice to send, from 0 to 100, with at
     * most two decimal places, e.g. 12.5. Without one, Stripe sends the whole of each invoice.
     *
     * @throws ExceptionInvalidValue - if the destination isn't an account's ID, or the percentage is out of range, or
     * has more than two decimal places.
     */
    public function __construct(string $destinationAccountId, ?float $amountPercent = null)
    {
        if (!str_starts_with($destinationAccountId, 'acct_'))
        {
            throw new ExceptionInvalidValue(
                "Funds can only be sent to a connected account, whose ID starts \"acct_\", not {$destinationAccountId}."
            );
        }

        $isValidPercent = (
               $amountPercent === null
            || ($amountPercent >= 0 && $amountPercent <= 100 && abs(round($amountPercent, 2) - $amountPercent) < 1e-9)
        );

        if (!$isValidPercent)
        {
            throw new ExceptionInvalidValue(
                "The percentage to send must be from 0 to 100, with at most two decimal places, but {$amountPercent} " .
                "was given."
            );
        }

        $this->m_destinationAccountId = $destinationAccountId;
        $this->m_amountPercent = $amountPercent;
    }


    /**
     * @return array{destination: string, amount_percent?: float}
     */
    public function toArray(): array
    {
        $arrayForm = ['destination' => $this->m_destinationAccountId];

        if ($this->m_amountPercent !== null)
        {
            $arrayForm['amount_percent'] = $this->m_amountPercent;
        }

        return $arrayForm;
    }
}
