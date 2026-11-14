<?php

/*
 * The data for an inline price, for an item in a subscription.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-price_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\TaxBehavior;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;


final readonly class PriceDataForSubscriptionItem implements Arrayable
{
    private Currency $m_currency;
    private int $m_unitPriceAmount;
    private string $m_productId;
    private RecurringConfig $m_recurring;
    private ?TaxBehavior $m_taxBehavior;


    /**
     * @param Currency $currency - the currency of the price.
     *
     * @param int $unitPriceAmount - A non-negative integer in cents representing how much to charge. E.g. 500 for 5
     * USD.
     *
     * @param string $productId - the ID of the product this price data is for.
     *
     * @param RecurringConfig $recurring - how often the price is charged.
     *
     * @param TaxBehavior|null $taxBehavior - whether the price includes tax.
     *
     * @throws ExceptionInvalidValue - if $unitPriceAmount is negative.
     */
    public function __construct(
        Currency $currency,
        int $unitPriceAmount,
        string $productId,
        RecurringConfig $recurring,
        ?TaxBehavior $taxBehavior = null
    )
    {
        if ($unitPriceAmount < 0)
        {
            throw new ExceptionInvalidValue("The unit amount must not be negative, but {$unitPriceAmount} was given.");
        }

        $this->m_currency = $currency;
        $this->m_unitPriceAmount = $unitPriceAmount;
        $this->m_productId = $productId;
        $this->m_recurring = $recurring;
        $this->m_taxBehavior = $taxBehavior;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = [
            'currency' => $this->m_currency->value,
            'unit_amount' => $this->m_unitPriceAmount,
            'product' => $this->m_productId,
            'recurring' => $this->m_recurring->toArray(),
        ];

        if ($this->m_taxBehavior !== null)
        {
            $arrayForm['tax_behavior'] = $this->m_taxBehavior->value;
        }

        return $arrayForm;
    }
}
