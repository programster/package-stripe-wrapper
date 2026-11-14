<?php

/*
 * A line item in a one-time payment checkout session.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;


readonly class SinglePaymentLineItem implements Arrayable
{
    private int $m_quantity;
    private string|PriceDataForSinglePayment $m_priceDataOrPriceId;
    private ?AdjustableQuantityConfig $m_adjustableQuantityConfig;
    private ?TaxRates $m_taxRates;


    /**
     * Create a LineItem object: https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items
     *
     * @param int $quantity - the number of this item to be purchased/sold.
     *
     * @param string|PriceDataForSinglePayment $priceDataOrPriceId - either a pricedata object to create an inline
     * price, or the ID of an existing price/plan object within Stripe.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price
     *
     * @param AdjustableQuantityConfig|null $adjustableQuantityConfig - provide this if you want the user to be able to
     * adjust the quantity. If not provided, then defaults to null for a fixed quantity.
     *
     * @param TaxRates|null $taxRates - the IDs of tax rate objects that could apply to the line item
     * depending on the customers billing/shipping address: https://docs.stripe.com/api/tax_rates
     */
    public function __construct(
        int $quantity,
        string|PriceDataForSinglePayment $priceDataOrPriceId,
        ?AdjustableQuantityConfig $adjustableQuantityConfig = null,
        ?TaxRates $taxRates = null,
    )
    {
        $this->m_quantity = $quantity;
        $this->m_priceDataOrPriceId = $priceDataOrPriceId;
        $this->m_adjustableQuantityConfig = $adjustableQuantityConfig;
        $this->m_taxRates = $taxRates;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = ['quantity' => $this->m_quantity];

        if ($this->m_adjustableQuantityConfig !== null)
        {
            $arrayForm['adjustable_quantity'] = $this->m_adjustableQuantityConfig->toArray();
        }

        if ($this->m_taxRates !== null)
        {
            if ($this->m_taxRates->getIsDynamic())
            {
                $arrayForm['dynamic_tax_rates'] = $this->m_taxRates->getTaxRates();
            }
            else
            {
                $arrayForm['tax_rates'] = $this->m_taxRates->getTaxRates();
            }
        }

        if (is_string($this->m_priceDataOrPriceId))
        {
            $arrayForm['price'] = $this->m_priceDataOrPriceId;
        }
        else
        {
            $arrayForm['price_data'] = $this->m_priceDataOrPriceId->toArray();
        }

        return $arrayForm;
    }
}
