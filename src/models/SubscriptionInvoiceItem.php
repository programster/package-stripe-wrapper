<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\Collections\DiscountCollection;
use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Interfaces\Arrayable;
use Stripe\InvoiceRenderingTemplate;

readonly class SubscriptionInvoiceItem implements Arrayable
{
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
        private string|PriceDataForInvoiceItem $priceIdOrPriceData,
        private int $quantity,
        private ?DiscountCollection $discounts = null,
        private ?Metadata $metadata = null,
        private ?InvoiceItemPeriod $period = null,
        private ?StringCollection $taxRates = null,
    )
    {

    }


    public function toArray(): array
    {
        $arrayForm = [
            'quantity' => $this->quantity,
        ];

        if ($this->discounts !== null)
        {
            $arrayForm['discounts'] = $this->discounts->toStripeArrayForm();
        }

        if ($this->metadata !== null)
        {
            $arrayForm['metadata'] = $this->metadata->toStripeArrayForm();
        }

        if ($this->period !== null)
        {
            $arrayForm['period'] = $this->period->toArray();
        }

        if (is_string($this->priceIdOrPriceData))
        {
            $arrayForm['price'] = $this->priceIdOrPriceData;
        }
        else
        {
            $arrayForm['price_data'] = $this->priceIdOrPriceData->toArray();
        }

        if ($this->taxRates !== null)
        {
            $arrayForm['tax_rates'] = $this->taxRates->toArray();
        }

        return $arrayForm;
    }
}