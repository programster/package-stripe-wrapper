<?php

/*
 * An item in a subscription, for when creating a subscription directly, rather than through a checkout session.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-items
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\DiscountCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;


readonly class SubscriptionItem implements Arrayable
{
    private int $m_quantity;
    private string|PriceDataForSubscriptionItem $m_priceDataOrPriceId;
    private ?Metadata $m_metadata;
    private ?DiscountCollection $m_discounts;
    private ?BillingThresholds $m_billingThresholds;
    private ?TaxRates $m_taxRates;


    /**
     * Create a line item for when creating a subscription object.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items
     *
     * @param int $quantity - the number of this item to be purchased/sold.
     *
     * @param string|PriceDataForSubscriptionItem $priceDataOrPriceId - either a pricedata object to create an inline
     * price, or the ID of an existing price/plan object within Stripe.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-price
     *
     * @param AdjustableQuantityConfig|null $adjustableQuantityConfig - must be left as null, as Stripe doesn't support
     * an adjustable quantity on a subscription's items. It is only here so that existing code keeps working, and will
     * be removed in the next major version.
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful
     * for storing additional information about the object in a structured format. Individual keys can be unset by
     * posting an empty value to them. All keys can be unset by posting an empty value to metadata.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-metadata
     *
     * @param DiscountCollection|null $discounts - The coupons to redeem into discounts for the
     * subscription item.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-discounts
     *
     * @param BillingThresholds|null $billingThresholds - Define thresholds at which an invoice will be sent, and the
     * subscription advanced to a new billing period. Pass an empty string to remove previously-defined thresholds.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-billing_thresholds
     *
     * @param TaxRates|null $taxRates - the IDs of tax rate objects that apply to the item. They can't be dynamic, as
     * Stripe only supports dynamic tax rates on checkout line items. https://docs.stripe.com/api/tax_rates
     *
     * @throws ExceptionInvalidValue - if an adjustable quantity, or dynamic tax rates, are given.
     */
    public function __construct(
        int $quantity,
        string|PriceDataForSubscriptionItem $priceDataOrPriceId,
        ?AdjustableQuantityConfig $adjustableQuantityConfig = null,
        ?Metadata $metadata = null,
        ?DiscountCollection $discounts = null,
        ?BillingThresholds $billingThresholds = null,
        ?TaxRates $taxRates = null,
    )
    {
        // Stripe rejects these for a subscription's items, so catch them here, where the mistake was made.
        if ($adjustableQuantityConfig !== null)
        {
            throw new ExceptionInvalidValue(
                "Stripe doesn't support an adjustable quantity on a subscription's items, only on checkout line items."
            );
        }

        if ($taxRates !== null && $taxRates->getIsDynamic())
        {
            throw new ExceptionInvalidValue(
                "Stripe doesn't support dynamic tax rates on a subscription's items, only on checkout line items."
            );
        }

        $this->m_quantity = $quantity;
        $this->m_priceDataOrPriceId = $priceDataOrPriceId;
        $this->m_metadata = ($metadata !== null) ? clone $metadata : null;
        $this->m_discounts = ($discounts !== null) ? clone $discounts : null;
        $this->m_billingThresholds = $billingThresholds;
        $this->m_taxRates = $taxRates;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = ['quantity' => $this->m_quantity];

        if ($this->m_taxRates !== null)
        {
            $arrayForm['tax_rates'] = $this->m_taxRates->getTaxRates();
        }

        if (is_string($this->m_priceDataOrPriceId))
        {
            $arrayForm['price'] = $this->m_priceDataOrPriceId;
        }
        else
        {
            $arrayForm['price_data'] = $this->m_priceDataOrPriceId->toArray();
        }

        if ($this->m_metadata !== null)
        {
            $arrayForm['metadata'] = $this->m_metadata->toStripeArrayForm();
        }

        if ($this->m_discounts !== null)
        {
            $arrayForm['discounts'] = $this->m_discounts->toStripeArrayForm();
        }

        if ($this->m_billingThresholds !== null)
        {
            $arrayForm['billing_thresholds'] = $this->m_billingThresholds->toStripeForm();
        }

        return $arrayForm;
    }
}
