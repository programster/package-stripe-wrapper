<?php

/*
 * A line item in a one-time payment checkout session, for an existing price, or one created inline.
 * Create one with forPrice() or forPriceData().
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;
use Programster\Stripe\Internal\StripeIds;


/**
 * @phpstan-import-type PriceDataForSinglePaymentArray from PriceDataForSinglePayment
 *
 * @phpstan-type SinglePaymentLineItemArray array{
 *     quantity: int,
 *     adjustable_quantity?: array{enabled: true, minimum: int, maximum: int},
 *     dynamic_tax_rates?: list<string>,
 *     tax_rates?: list<string>,
 *     price?: string,
 *     price_data?: PriceDataForSinglePaymentArray
 * }
 * @phpstan-type PriceParams array{price: string}|array{price_data: PriceDataForSinglePaymentArray}
 */
final readonly class SinglePaymentLineItem implements Arrayable
{
    private int $m_quantity;

    /** @var PriceParams */
    private array $m_priceParams;

    private ?AdjustableQuantityConfig $m_adjustableQuantityConfig;
    private ?TaxRates $m_taxRates;


    /**
     * This is private, so use forPrice() or forPriceData().
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
     * Create a line item for an existing price.
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
     * @return SinglePaymentLineItem
     *
     * @throws ExceptionInvalidValue - if $priceId is empty, or a product's ID.
     */
    public static function forPrice(
        string $priceId,
        int $quantity,
        ?AdjustableQuantityConfig $adjustableQuantityConfig = null,
        ?TaxRates $taxRates = null,
    ) : SinglePaymentLineItem
    {
        StripeIds::checkPriceId($priceId);
        return new SinglePaymentLineItem($quantity, ['price' => $priceId], $adjustableQuantityConfig, $taxRates);
    }


    /**
     * Create a line item with a price created inline.
     *
     * @param PriceDataForSinglePayment $priceData - the price.
     *
     * @param int $quantity - the number of this item to be purchased.
     *
     * @param AdjustableQuantityConfig|null $adjustableQuantityConfig - provide this if you want the customer to be
     * able to adjust the quantity. Leave it as null for a fixed quantity.
     *
     * @param TaxRates|null $taxRates - the IDs of tax rate objects that could apply to the line item, depending on the
     * customer's billing or shipping address: https://docs.stripe.com/api/tax_rates
     *
     * @return SinglePaymentLineItem
     */
    public static function forPriceData(
        PriceDataForSinglePayment $priceData,
        int $quantity,
        ?AdjustableQuantityConfig $adjustableQuantityConfig = null,
        ?TaxRates $taxRates = null,
    ) : SinglePaymentLineItem
    {
        $priceParams = ['price_data' => $priceData->toArray()];
        return new SinglePaymentLineItem($quantity, $priceParams, $adjustableQuantityConfig, $taxRates);
    }


    /**
     * @return SinglePaymentLineItemArray
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
