<?php

/*
 * The data for an inline price, for a line item in a one-time payment checkout session.
 * Create one with forProduct() or forProductData().
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;
use Programster\Stripe\Internal\StripeIds;


/**
 * @phpstan-import-type ProductDataArray from ProductData
 *
 * @phpstan-type PriceDataForSinglePaymentArray array{
 *     currency: string,
 *     unit_amount: int,
 *     product?: string,
 *     product_data?: ProductDataArray
 * }
 * @phpstan-type ProductParams array{product: string}|array{product_data: ProductDataArray}
 */
final readonly class PriceDataForSinglePayment implements Arrayable
{
    private Currency $m_currency;
    private int $m_unitAmount;

    /** @var ProductParams */
    private array $m_productParams;



    /**
     * This is private, so use forProduct() or forProductData(), whose parameters of the same names this takes.
     *
     * @param Currency $currency
     *
     * @param int $unitAmount
     *
     * @param ProductParams $productParams - the price's parameters for the product.
     *
     * @throws ExceptionInvalidValue - if $unitAmount is negative.
     */
    private function __construct(
        Currency $currency,
        int $unitAmount,
        array $productParams,
    )
    {
        if ($unitAmount < 0)
        {
            throw new ExceptionInvalidValue("The unit amount must not be negative, but {$unitAmount} was given.");
        }

        $this->m_currency = $currency;
        $this->m_unitAmount = $unitAmount;
        $this->m_productParams = $productParams;
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
     * @return PriceDataForSinglePayment
     *
     * @throws ExceptionInvalidValue - if $unitAmount is negative, or $productId is empty or a price's ID.
     */
    public static function forProduct(
        Currency $currency,
        int $unitAmount,
        string $productId,
    ) : PriceDataForSinglePayment
    {
        StripeIds::checkProductId($productId);
        return new PriceDataForSinglePayment($currency, $unitAmount, ['product' => $productId]);
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
     * @return PriceDataForSinglePayment
     *
     * @throws ExceptionInvalidValue - if $unitAmount is negative.
     */
    public static function forProductData(
        Currency $currency,
        int $unitAmount,
        ProductData $productData,
    ) : PriceDataForSinglePayment
    {
        return new PriceDataForSinglePayment($currency, $unitAmount, ['product_data' => $productData->toArray()]);
    }


    /**
     * @return PriceDataForSinglePaymentArray
     */
    public function toArray() : array
    {
        $arrayForm = [
            'currency' => $this->m_currency->value,
            'unit_amount' => $this->m_unitAmount,
        ];

        $arrayForm = array_merge($arrayForm, $this->m_productParams);

        return $arrayForm;
    }
}
