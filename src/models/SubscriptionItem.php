<?php

/*
 * An item in a subscription, for when creating a subscription directly, rather than through a checkout session, for
 * an existing price, or one created inline. Create one with createUsingExistingPrice() or createUsingInlinePrice().
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-items
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\DiscountCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;
use Programster\Stripe\Internal\StripeIds;


/**
 * @phpstan-import-type PriceDataForSubscriptionItemArray from PriceDataForSubscriptionItem
 *
 * @phpstan-import-type SubscriptionItemDiscountArray from SubscriptionItemDiscount
 *
 * @phpstan-type SubscriptionItemArray array{
 *     quantity: int,
 *     tax_rates?: list<string>,
 *     price?: string,
 *     price_data?: PriceDataForSubscriptionItemArray,
 *     metadata?: array<string, string>,
 *     discounts?: list<SubscriptionItemDiscountArray>,
 *     billing_thresholds?: array{usage_gte: int}|''
 * }
 * @phpstan-type PriceParams array{price: string}|array{price_data: PriceDataForSubscriptionItemArray}
 */
final readonly class SubscriptionItem implements Arrayable
{
    private int $m_quantity;

    /** @var PriceParams */
    private array $m_priceParams;

    private ?Metadata $m_metadata;
    private ?DiscountCollection $m_discounts;
    private ?BillingThresholds $m_billingThresholds;
    private ?TaxRates $m_taxRates;


    /**
     * This is private, so use createUsingExistingPrice() or createUsingInlinePrice(), whose parameters of the same
     * names this takes.
     *
     * @param int $quantity
     *
     * @param PriceParams $priceParams - the item's parameters for the price.
     *
     * @param Metadata|null $metadata
     *
     * @param DiscountCollection|null $discounts
     *
     * @param BillingThresholds|null $billingThresholds
     *
     * @param TaxRates|null $taxRates
     *
     * @throws ExceptionInvalidValue - if dynamic tax rates are given.
     */
    private function __construct(
        int $quantity,
        array $priceParams,
        ?Metadata $metadata,
        ?DiscountCollection $discounts,
        ?BillingThresholds $billingThresholds,
        ?TaxRates $taxRates,
    )
    {
        // Stripe rejects these for a subscription's items, so catch them here, where the mistake was made.
        if ($taxRates !== null && $taxRates->getIsDynamic())
        {
            throw new ExceptionInvalidValue(
                "Stripe doesn't support dynamic tax rates on a subscription's items, only on checkout line items."
            );
        }

        $this->m_quantity = $quantity;
        $this->m_priceParams = $priceParams;
        $this->m_metadata = $metadata;
        $this->m_discounts = $discounts;
        $this->m_billingThresholds = $billingThresholds;
        $this->m_taxRates = $taxRates;
    }


    /**
     * Create an item for a price that's already in Stripe, by its ID, so the price has to have been created beforehand
     * (in the dashboard or through the API).
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items
     *
     * @param string $priceId - the price's ID, e.g. "price_123" (or a legacy plan's ID).
     *
     * @param int $quantity - the number of this item to be subscribed to.
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful
     * for storing additional information about the object in a structured format.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-metadata
     *
     * @param DiscountCollection|null $discounts - The coupons to redeem into discounts for the subscription item.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-discounts
     *
     * @param BillingThresholds|null $billingThresholds - Define thresholds at which an invoice will be sent, and the
     * subscription advanced to a new billing period.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-billing_thresholds
     *
     * @param TaxRates|null $taxRates - the IDs of tax rate objects that apply to the item. They can't be dynamic, as
     * Stripe only supports dynamic tax rates on checkout line items. https://docs.stripe.com/api/tax_rates
     *
     * @return SubscriptionItem
     *
     * @throws ExceptionInvalidValue - if $priceId is empty or a product's ID, or dynamic tax rates are given.
     */
    public static function createUsingExistingPrice(
        string $priceId,
        int $quantity,
        ?Metadata $metadata = null,
        ?DiscountCollection $discounts = null,
        ?BillingThresholds $billingThresholds = null,
        ?TaxRates $taxRates = null,
    ) : SubscriptionItem
    {
        StripeIds::checkPriceId($priceId);
        $priceParams = ['price' => $priceId];
        return new SubscriptionItem($quantity, $priceParams, $metadata, $discounts, $billingThresholds, $taxRates);
    }


    /**
     * Create an item with its price described here, which Stripe creates along with it, so nothing has to be
     * set up in Stripe beforehand.
     * https://docs.stripe.com/api/subscriptions/create#create_subscription-items-price_data
     *
     * @param PriceDataForSubscriptionItem $priceData - the price.
     *
     * @param int $quantity - the number of this item to be subscribed to.
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful
     * for storing additional information about the object in a structured format.
     *
     * @param DiscountCollection|null $discounts - The coupons to redeem into discounts for the subscription item.
     *
     * @param BillingThresholds|null $billingThresholds - Define thresholds at which an invoice will be sent, and the
     * subscription advanced to a new billing period.
     *
     * @param TaxRates|null $taxRates - the IDs of tax rate objects that apply to the item. They can't be dynamic, as
     * Stripe only supports dynamic tax rates on checkout line items. https://docs.stripe.com/api/tax_rates
     *
     * @return SubscriptionItem
     *
     * @throws ExceptionInvalidValue - if dynamic tax rates are given.
     */
    public static function createUsingInlinePrice(
        PriceDataForSubscriptionItem $priceData,
        int $quantity,
        ?Metadata $metadata = null,
        ?DiscountCollection $discounts = null,
        ?BillingThresholds $billingThresholds = null,
        ?TaxRates $taxRates = null,
    ) : SubscriptionItem
    {
        $priceParams = ['price_data' => $priceData->toArray()];
        return new SubscriptionItem($quantity, $priceParams, $metadata, $discounts, $billingThresholds, $taxRates);
    }


    /**
     * @return SubscriptionItemArray
     */
    public function toArray() : array
    {
        $arrayForm = ['quantity' => $this->m_quantity];

        if ($this->m_taxRates !== null)
        {
            $arrayForm['tax_rates'] = $this->m_taxRates->toStripeArrayForm();
        }

        $arrayForm = array_merge($arrayForm, $this->m_priceParams);

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
