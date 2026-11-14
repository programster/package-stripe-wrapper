<?php

/*
 * An amount of money from a Stripe response: the amount, in the currency's smallest unit (e.g. pennies for GBP, or
 * cents for USD), and the currency.
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Enums\Currency;


final readonly class Money
{
    private int $m_amount;
    private string $m_currency;


    /**
     * @param int $amount - the amount, in the currency's smallest unit, e.g. 1050 for £10.50.
     * @param string $currency - the currency's code, as Stripe sends it, e.g. "gbp".
     */
    public function __construct(int $amount, string $currency)
    {
        $this->m_amount = $amount;
        $this->m_currency = $currency;
    }


    /**
     * Check whether the money is in a currency. This takes the Currency enum, so the comparison can't be misspelt.
     * @param Currency $currency - the currency to check for.
     * @return bool
     */
    public function hasCurrency(Currency $currency): bool
    {
        return $this->m_currency === $currency->value;
    }


    # Accessors
    public function getAmount(): int { return $this->m_amount; }
    public function getCurrency(): string { return $this->m_currency; }
}
