<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Interfaces\Arrayable;

readonly class SubscriptionCheckoutLineItem implements Arrayable
{
    /**
     * Create a LineItem object: https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items
     *
     * @param int $quantity - the number of this item to be purhased/sold.
     *
     * @param string|PriceDataForSubscription $priceDataOrPriceId - either a pricedata object to create an inline price, or the ID of
     * an existing price/plan object within Stripe.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price
     *
     * @param ?AdjustableQuantityConfig $adjustableQuantityConfig - $adjustableQuantityConfig - provide this if you
     * want the user to be able to adjust the quantity. If not provided, then defaults to null for a fixed quantity.
     *
     * @param TaxRates|null $taxRates - the IDs of tax rate objects that could apply to the line item
     * depending on the customers billing/shipping address: https://docs.stripe.com/api/tax_rates
     */
    public function __construct(
        private readonly int                             $quantity,
        private readonly string|PriceDataForSubscription $priceDataOrPriceId,
        private readonly ?AdjustableQuantityConfig       $adjustableQuantityConfig = null,
        private readonly ?TaxRates                       $taxRates = null,
    )
    {

    }

    public function toArray(): array
    {
        $arrayForm = [
            'quantity' => $this->quantity,
        ];

        if ($this->adjustableQuantityConfig !== null)
        {
            $arrayForm['adjustable_quantity'] = $this->adjustableQuantityConfig->toArray();
        }

        if ($this->taxRates !== null)
        {
            if ($this->taxRates->getIsDynamic())
            {
                $arrayForm['dynamic_tax_rates'] = $this->taxRates->getTaxRates();
            }
            else
            {
                $arrayForm['tax_rates'] = $this->taxRates->getTaxRates();
            }
        }

        if (is_string($this->priceDataOrPriceId))
        {
            $arrayForm['price'] = $this->priceDataOrPriceId;
        }
        else
        {
            $arrayForm['price_data'] = $this->priceDataOrPriceId->toArray();
        }

        return $arrayForm;
    }
}