<?php

/*
 * The data for an inline price, for a line item in a subscription checkout session.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\TaxBehavior;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;


readonly class PriceDataForSubscriptionCheckoutSession implements Arrayable
{
    private Currency $m_currency;
    private int $m_unitAmount;
    private ProductData|string $m_productDataOrId;
    private ?RecurringConfig $m_recurring;
    private ?TaxBehavior $m_taxBehavior;


    /**
     * @param Currency $currency - the currency of the price.
     *
     * @param int $unitAmount - A non-negative integer in cents representing how much to charge. E.g. 500 for 5 USD.
     *
     * @param ProductData|string $productDataOrId - either the ID of an already registered product in Stripe, or the
     * data to create a new inline product.
     *
     * @param RecurringConfig|null $recurring - how often the price is charged. Leave as null for an item that is only
     * paid for once.
     *
     * @param TaxBehavior|null $taxBehavior - whether the price includes tax.
     *
     * @throws ExceptionInvalidValue - if $unitAmount is negative.
     */
    public function __construct(
        Currency $currency,
        int $unitAmount,
        ProductData|string $productDataOrId,
        ?RecurringConfig $recurring = null,
        ?TaxBehavior $taxBehavior = null
    )
    {
        if ($unitAmount < 0)
        {
            throw new ExceptionInvalidValue("The unit amount must not be negative, but {$unitAmount} was given.");
        }

        $this->m_currency = $currency;
        $this->m_unitAmount = $unitAmount;
        $this->m_productDataOrId = $productDataOrId;
        $this->m_recurring = $recurring;
        $this->m_taxBehavior = $taxBehavior;
    }


    public function toArray() : array
    {
        $arrayForm = [
            'currency' => $this->m_currency->value,
            'unit_amount' => $this->m_unitAmount,
        ];

        if (is_string($this->m_productDataOrId))
        {
            $arrayForm['product'] = $this->m_productDataOrId;
        }
        else
        {
            $arrayForm['product_data'] = $this->m_productDataOrId->toArray();
        }

        if ($this->m_recurring !== null)
        {
            $arrayForm['recurring'] = $this->m_recurring->toArray();
        }

        if ($this->m_taxBehavior !== null)
        {
            $arrayForm['tax_behavior'] = $this->m_taxBehavior->value;
        }

        return $arrayForm;
    }
}
