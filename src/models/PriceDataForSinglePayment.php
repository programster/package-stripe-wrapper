<?php

/*
 * The data for an inline price, for a line item in a one-time payment checkout session.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;


readonly class PriceDataForSinglePayment implements Arrayable
{
    private Currency $m_currency;
    private int $m_unitAmount;
    private ProductData|string $m_productDataOrId;


    /**
     * @param Currency $currency - the currency to charge the item in.
     *
     * @param int $unitAmount - A non-negative integer in cents representing how much to charge.
     *
     * @param ProductData|string $productDataOrId - either the ID of an already registered product in Stripe, or the
     * data to create a new inline product.
     *
     * @throws ExceptionInvalidValue - if $unitAmount is negative.
     */
    public function __construct(
        Currency $currency,
        int $unitAmount,
        ProductData|string $productDataOrId,
    )
    {
        if ($unitAmount < 0)
        {
            throw new ExceptionInvalidValue("The unit amount must not be negative, but {$unitAmount} was given.");
        }

        $this->m_currency = $currency;
        $this->m_unitAmount = $unitAmount;
        $this->m_productDataOrId = $productDataOrId;
    }


    /**
     * @return array<string, mixed>
     */
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

        return $arrayForm;
    }
}
