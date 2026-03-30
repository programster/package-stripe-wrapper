<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\DiscountCollection;
use Programster\Stripe\Interfaces\Arrayable;

readonly class SubscriptionItem implements Arrayable
{
    /**
     * Create a line item for when creating a subscripton object.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items
     *
     * @param int $quantity - the number of this item to be purhased/sold.
     *
     * @param string|PriceDataForSubscriptionCheckoutSession $priceDataOrPriceId - either a pricedata object to create an inline price, or the ID of
     * an existing price/plan object within Stripe.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price
     *
     * @param ?AdjustableQuantityConfig $adjustableQuantityConfig - $adjustableQuantityConfig - provide this if you
     * want the user to be able to adjust the quantity. If not provided, then defaults to null for a fixed quantity.
     *
     * @param ?Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful
     * for storing additional information about the object in a structured format. Individual keys can be unset by
     * posting an empty value to them. All keys can be unset by posting an empty value to metadata.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-metadata
     *
     * @param ?DiscountCollection|null $discounts - The coupons to redeem into discounts for the
     * subscription item.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-discounts
     *
     * @param ?BillingThresholds|null $billingThresholds - Define thresholds at which an invoice will be sent, and the
     * subscription advanced to a new billing period. Pass an empty string to remove previously-defined thresholds.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-billing_thresholds
     *
     * @param ?TaxRates|null $taxRates - the IDs of tax rate objects that could apply to the line item
     * depending on the customers billing/shipping address: https://docs.stripe.com/api/tax_rates
     */
    public function __construct(
        private int                                 $quantity,
        private string|PriceDataForSubscriptionItem $priceDataOrPriceId,
        private ?AdjustableQuantityConfig           $adjustableQuantityConfig = null,
        private ?Metadata                           $metadata = null,
        private ?DiscountCollection                 $discounts = null,
        private ?BillingThresholds                  $billingThresholds = null,
        private ?TaxRates                           $taxRates = null,
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

        if ($this->metadata !== null)
        {
            $arrayForm['metadata'] = $this->metadata->toStripeArrayForm();
        }

        if ($this->discounts !== null)
        {
            $arrayForm['discounts'] = $this->discounts->toStripeArrayForm();
        }

        if ($this->billingThresholds !== null)
        {
            $arrayForm['billing_thresholds'] = $this->billingThresholds->toStripeForm();
        }

        return $arrayForm;
    }
}