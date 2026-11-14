<?php

/*
 * An invoice item to add to a subscription's next invoice, for an existing price, or one created inline. Create one
 * with forPrice() or forPriceData().
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\DiscountCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;
use Programster\Stripe\Internal\StripeIds;


final readonly class SubscriptionInvoiceItem implements Arrayable
{
    /** @var array<string, mixed> */
    private array $m_priceParams;

    private int $m_quantity;
    private ?DiscountCollection $m_discounts;
    private ?Metadata $m_metadata;
    private ?InvoiceItemPeriod $m_period;
    private ?StringCollection $m_taxRates;


    /**
     * This is private, so use forPrice() or forPriceData(), whose parameters of the same names this takes.
     *
     * @param array<string, mixed> $priceParams - the item's parameters for the price.
     *
     * @param int $quantity
     *
     * @param DiscountCollection|null $discounts
     *
     * @param Metadata|null $metadata
     *
     * @param InvoiceItemPeriod|null $period
     *
     * @param StringCollection|null $taxRates
     */
    private function __construct(
        array $priceParams,
        int $quantity,
        ?DiscountCollection $discounts,
        ?Metadata $metadata,
        ?InvoiceItemPeriod $period,
        ?StringCollection $taxRates,
    )
    {
        $this->m_priceParams = $priceParams;
        $this->m_quantity = $quantity;
        $this->m_discounts = $discounts;
        $this->m_metadata = $metadata;
        $this->m_period = $period;
        $this->m_taxRates = $taxRates;
    }


    /**
     * Create an invoice item for an existing price.
     *
     * @param string $priceId - the price's ID, e.g. "price_123".
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
     *
     * @return SubscriptionInvoiceItem
     *
     * @throws ExceptionInvalidValue - if $priceId is empty, or a product's ID.
     */
    public static function forPrice(
        string $priceId,
        int $quantity,
        ?DiscountCollection $discounts = null,
        ?Metadata $metadata = null,
        ?InvoiceItemPeriod $period = null,
        ?StringCollection $taxRates = null,
    ) : SubscriptionInvoiceItem
    {
        StripeIds::checkPriceId($priceId);
        $priceParams = ['price' => $priceId];
        return new SubscriptionInvoiceItem($priceParams, $quantity, $discounts, $metadata, $period, $taxRates);
    }


    /**
     * Create an invoice item with a price created inline.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-price_data
     *
     * @param PriceDataForInvoiceItem $priceData - the price.
     *
     * @param int $quantity - Quantity for this item
     *
     * @param DiscountCollection|null $discounts - The coupons to redeem into discounts for the item.
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object.
     *
     * @param InvoiceItemPeriod|null $period - The period associated with this invoice item. If not set,
     * period.start.type defaults to max_item_period_start and period.end.type defaults to min_item_period_end.
     *
     * @param StringCollection|null $taxRates - The tax rates which apply to the item. When set, the default_tax_rates
     * do not apply to this item.
     *
     * @return SubscriptionInvoiceItem
     */
    public static function forPriceData(
        PriceDataForInvoiceItem $priceData,
        int $quantity,
        ?DiscountCollection $discounts = null,
        ?Metadata $metadata = null,
        ?InvoiceItemPeriod $period = null,
        ?StringCollection $taxRates = null,
    ) : SubscriptionInvoiceItem
    {
        $priceParams = ['price_data' => $priceData->toArray()];
        return new SubscriptionInvoiceItem($priceParams, $quantity, $discounts, $metadata, $period, $taxRates);
    }


    /**
     * @return array<string, mixed>
     */
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

        $arrayForm = array_merge($arrayForm, $this->m_priceParams);

        if ($this->m_taxRates !== null)
        {
            $arrayForm['tax_rates'] = $this->m_taxRates->toArray();
        }

        return $arrayForm;
    }
}
