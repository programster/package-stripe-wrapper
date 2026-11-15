<?php

/*
 * A line item in a subscription checkout session, which may be paid for once, or recurring, for an existing
 * price, or one created inline.
 * Create one with createUsingExistingPrice() or createUsingInlinePrice().
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;
use Programster\Stripe\Internal\StripeIds;


/**
 * @phpstan-import-type PriceDataForSubscriptionCheckoutSessionArray from PriceDataForSubscriptionCheckoutSession
 *
 * @phpstan-type SubscriptionCheckoutLineItemArray array{
 *     quantity: int,
 *     adjustable_quantity?: array{enabled: true, minimum: int, maximum: int},
 *     dynamic_tax_rates?: list<string>,
 *     tax_rates?: list<string>,
 *     price?: string,
 *     price_data?: PriceDataForSubscriptionCheckoutSessionArray
 * }
 * @phpstan-type PriceParams array{price: string}|array{price_data: PriceDataForSubscriptionCheckoutSessionArray}
 */
final readonly class SubscriptionCheckoutLineItem implements Arrayable
{
    private int $m_quantity;

    /** @var PriceParams */
    private array $m_priceParams;

    private ?AdjustableQuantityConfig $m_adjustableQuantityConfig;
    private ?TaxRates $m_taxRates;


    /**
     * This is private, so use createUsingExistingPrice() or createUsingInlinePrice().
     *
     * @param int $quantity - the number of this item to be purchased.
     *
     * @param PriceParams $priceParams - the line item's parameters for the price.
     *
     * @param AdjustableQuantityConfig|null $adjustableQuantityConfig - whether the customer can change the quantity.
     *
     * @param TaxRates|null $taxRates - the tax rates that could apply to the line item.
     */
    private function __construct(
        int $quantity,
        array $priceParams,
        ?AdjustableQuantityConfig $adjustableQuantityConfig,
        ?TaxRates $taxRates,
    )
    {
        $this->m_quantity = $quantity;
        $this->m_priceParams = $priceParams;
        $this->m_adjustableQuantityConfig = $adjustableQuantityConfig;
        $this->m_taxRates = $taxRates;
    }


    /**
     * Create a line item for a price that's already in Stripe, by its ID, so the price has to have been created
     * beforehand (in the dashboard or through the API).
     *
     * @param string $priceId - the price's ID, e.g. "price_123" (or a legacy plan's ID).
     *
     * @param int $quantity - the number of this item to be purchased.
     *
     * @param AdjustableQuantityConfig|null $adjustableQuantityConfig - provide this if you want the customer to be
     * able to adjust the quantity. Leave it as null for a fixed quantity.
     *
     * @param TaxRates|null $taxRates - the IDs of tax rate objects that could apply to the line item, depending on the
     * customer's billing or shipping address: https://docs.stripe.com/api/tax_rates
     *
     * @return SubscriptionCheckoutLineItem
     *
     * @throws ExceptionInvalidValue - if $priceId is empty, or a product's ID.
     */
    public static function createUsingExistingPrice(
        string $priceId,
        int $quantity,
        ?AdjustableQuantityConfig $adjustableQuantityConfig = null,
        ?TaxRates $taxRates = null,
    ) : SubscriptionCheckoutLineItem
    {
        StripeIds::checkPriceId($priceId);
        return new SubscriptionCheckoutLineItem($quantity, ['price' => $priceId], $adjustableQuantityConfig, $taxRates);
    }


    /**
     * Create a line item with its price described here, which Stripe creates along with it, so nothing has to be
     * set up in Stripe beforehand.
     *
     * @param PriceDataForSubscriptionCheckoutSession $priceData - the price.
     *
     * @param int $quantity - the number of this item to be purchased.
     *
     * @param AdjustableQuantityConfig|null $adjustableQuantityConfig - provide this if you want the customer to be
     * able to adjust the quantity. Leave it as null for a fixed quantity.
     *
     * @param TaxRates|null $taxRates - the IDs of tax rate objects that could apply to the line item, depending on the
     * customer's billing or shipping address: https://docs.stripe.com/api/tax_rates
     *
     * @return SubscriptionCheckoutLineItem
     */
    public static function createUsingInlinePrice(
        PriceDataForSubscriptionCheckoutSession $priceData,
        int $quantity,
        ?AdjustableQuantityConfig $adjustableQuantityConfig = null,
        ?TaxRates $taxRates = null,
    ) : SubscriptionCheckoutLineItem
    {
        $priceParams = ['price_data' => $priceData->toArray()];
        return new SubscriptionCheckoutLineItem($quantity, $priceParams, $adjustableQuantityConfig, $taxRates);
    }


    /**
     * @return SubscriptionCheckoutLineItemArray
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
                $arrayForm['dynamic_tax_rates'] = $this->m_taxRates->toStripeArrayForm();
            }
            else
            {
                $arrayForm['tax_rates'] = $this->m_taxRates->toStripeArrayForm();
            }
        }

        return array_merge($arrayForm, $this->m_priceParams);
    }
}
