<?php

/*
 * An invoice item to add to a subscription's next invoice.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\DiscountCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Interfaces\Arrayable;


readonly class SubscriptionInvoiceItem implements Arrayable
{
    private string|PriceDataForInvoiceItem $m_priceIdOrPriceData;
    private int $m_quantity;
    private ?DiscountCollection $m_discounts;
    private ?Metadata $m_metadata;
    private ?InvoiceItemPeriod $m_period;
    private ?StringCollection $m_taxRates;


    /**
     * @param string|PriceDataForInvoiceItem $priceIdOrPriceData - either the ID of the price object in stripe
     * for this item, or an object representing the price data to use.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-price_data
     *
     * @param int $quantity - Quantity for this item
     *
     * @param DiscountCollection|null $discounts - The coupons to redeem into discounts for the item.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-discounts
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful for
     * storing additional information about the object in a structured format. Individual keys can be unset by posting
     * an empty value to them. All keys can be unset by posting an empty value to metadata.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-metadata
     *
     * @param InvoiceItemPeriod|null $period - The period associated with this invoice item. If not set,
     * period.start.type defaults to max_item_period_start and period.end.type defaults to min_item_period_end.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-period
     *
     * @param StringCollection|null $taxRates - The tax rates which apply to the item. When set, the default_tax_rates
     * do not apply to this item.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-tax_rates
     */
    public function __construct(
        string|PriceDataForInvoiceItem $priceIdOrPriceData,
        int $quantity,
        ?DiscountCollection $discounts = null,
        ?Metadata $metadata = null,
        ?InvoiceItemPeriod $period = null,
        ?StringCollection $taxRates = null,
    )
    {
        $this->m_priceIdOrPriceData = $priceIdOrPriceData;
        $this->m_quantity = $quantity;
        $this->m_discounts = ($discounts !== null) ? clone $discounts : null;
        $this->m_metadata = ($metadata !== null) ? clone $metadata : null;
        $this->m_period = $period;
        $this->m_taxRates = ($taxRates !== null) ? clone $taxRates : null;
    }


    public function toArray() : array
    {
        $arrayForm = ['quantity' => $this->m_quantity];

        if ($this->m_discounts !== null)
        {
            $arrayForm['discounts'] = $this->m_discounts->toStripeArrayForm();
        }

        if ($this->m_metadata !== null)
        {
            $arrayForm['metadata'] = $this->m_metadata->toStripeArrayForm();
        }

        if ($this->m_period !== null)
        {
            $arrayForm['period'] = $this->m_period->toArray();
        }

        if (is_string($this->m_priceIdOrPriceData))
        {
            $arrayForm['price'] = $this->m_priceIdOrPriceData;
        }
        else
        {
            $arrayForm['price_data'] = $this->m_priceIdOrPriceData->toArray();
        }

        if ($this->m_taxRates !== null)
        {
            $arrayForm['tax_rates'] = $this->m_taxRates->toArray();
        }

        return $arrayForm;
    }
}
