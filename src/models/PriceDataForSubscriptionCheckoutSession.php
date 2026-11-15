<?php

/*
 * The data for an inline price, for a line item in a subscription checkout session.
 * Create one with forProduct() or forProductData().
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\TaxBehavior;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;
use Programster\Stripe\Internal\StripeIds;


/**
 * @phpstan-import-type ProductDataArray from ProductData
 *
 * @phpstan-import-type RecurringArray from RecurringConfig
 *
 * @phpstan-type PriceDataForSubscriptionCheckoutSessionArray array{
 *     currency: string,
 *     unit_amount: int,
 *     product?: string,
 *     product_data?: ProductDataArray,
 *     recurring?: RecurringArray,
 *     tax_behavior?: string
 * }
 * @phpstan-type ProductParams array{product: string}|array{product_data: ProductDataArray}
 */
final readonly class PriceDataForSubscriptionCheckoutSession implements Arrayable
{
    private Currency $m_currency;
    private int $m_unitAmount;

    /** @var ProductParams */
    private array $m_productParams;

    private ?RecurringConfig $m_recurring;
    private ?TaxBehavior $m_taxBehavior;


    /**
     * This is private, so use forProduct() or forProductData(), whose parameters of the same names this takes.
     *
     * @param Currency $currency
     *
     * @param int $unitAmount
     *
     * @param ProductParams $productParams - the price's parameters for the product.
     *
     * @param RecurringConfig|null $recurring
     *
     * @param TaxBehavior|null $taxBehavior
     *
     * @throws ExceptionInvalidValue - if $unitAmount is negative.
     */
    private function __construct(
        Currency $currency,
        int $unitAmount,
        array $productParams,
        ?RecurringConfig $recurring,
        ?TaxBehavior $taxBehavior,
    )
    {
        if ($unitAmount < 0)
        {
            throw new ExceptionInvalidValue("The unit amount must not be negative, but {$unitAmount} was given.");
        }

        $this->m_currency = $currency;
        $this->m_unitAmount = $unitAmount;
        $this->m_productParams = $productParams;
        $this->m_recurring = $recurring;
        $this->m_taxBehavior = $taxBehavior;
    }


    /**
     * Create a price for an existing product.
     *
     * @param Currency $currency - the currency of the price.
     *
     * @param int $unitAmount - A non-negative integer in the currency's smallest unit (e.g. cents), representing how
     * much to charge. E.g. 500 for 5 USD.
     *
     * @param string $productId - the product's ID, e.g. "prod_123".
     *
     * @param RecurringConfig|null $recurring - how often the price is charged. Leave as null for an item that is only
     * paid for once.
     *
     * @param TaxBehavior|null $taxBehavior - whether the price includes tax.
     *
     * @return PriceDataForSubscriptionCheckoutSession
     *
     * @throws ExceptionInvalidValue - if $unitAmount is negative, or $productId is empty or a price's ID.
     */
    public static function forProduct(
        Currency $currency,
        int $unitAmount,
        string $productId,
        ?RecurringConfig $recurring = null,
        ?TaxBehavior $taxBehavior = null,
    ) : PriceDataForSubscriptionCheckoutSession
    {
        StripeIds::checkProductId($productId);
        $productParams = ['product' => $productId];
        return new self($currency, $unitAmount, $productParams, $recurring, $taxBehavior);
    }


    /**
     * Create a price for a product created inline.
     *
     * @param Currency $currency - the currency of the price.
     *
     * @param int $unitAmount - A non-negative integer in the currency's smallest unit (e.g. cents), representing how
     * much to charge. E.g. 500 for 5 USD.
     *
     * @param ProductData $productData - the product.
     *
     * @param RecurringConfig|null $recurring - how often the price is charged. Leave as null for an item that is only
     * paid for once.
     *
     * @param TaxBehavior|null $taxBehavior - whether the price includes tax.
     *
     * @return PriceDataForSubscriptionCheckoutSession
     *
     * @throws ExceptionInvalidValue - if $unitAmount is negative.
     */
    public static function forProductData(
        Currency $currency,
        int $unitAmount,
        ProductData $productData,
        ?RecurringConfig $recurring = null,
        ?TaxBehavior $taxBehavior = null,
    ) : PriceDataForSubscriptionCheckoutSession
    {
        $productParams = ['product_data' => $productData->toArray()];
        return new self($currency, $unitAmount, $productParams, $recurring, $taxBehavior);
    }


    /**
     * @return PriceDataForSubscriptionCheckoutSessionArray
     */
    public function toArray() : array
    {
        $arrayForm = [
            'currency' => $this->m_currency->value,
            'unit_amount' => $this->m_unitAmount,
        ];

        $arrayForm = array_merge($arrayForm, $this->m_productParams);

        if ($this->m_recurring !== null)
        {
            $arrayForm['recurring'] = $this->m_recurring->toArray();
        }

        if ($this->m_taxBehavior !== null)
        {
            $arrayForm['tax_behavior'] = $this->m_taxBehavior->value;
        }

        return $arrayForm;
    }
}
