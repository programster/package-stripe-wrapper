<?php

/*
 * The data for an inline price, for an invoice item added to a subscription.
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-add_invoice_items-price_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\TaxBehavior;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;
use Programster\Stripe\Internal\StripeIds;


/**
 * @phpstan-type PriceDataForInvoiceItemArray array{
 *     currency: string,
 *     product: string,
 *     unit_amount: int,
 *     tax_behavior?: string
 * }
 */
final readonly class PriceDataForInvoiceItem implements Arrayable
{
    private Currency $m_currency;
    private string $m_productId;
    private int $m_unitAmount;
    private ?TaxBehavior $m_taxBehavior;


    /**
     * @param Currency $currency - the currency of the price.
     *
     * @param string $productId - the ID of the product this price will belong to, e.g. "prod_123".
     *
     * @param int $unitAmount - A non-negative integer in cents representing how much to charge. E.g. 500 for 5 USD.
     *
     * @param TaxBehavior|null $taxBehavior - whether the price includes tax.
     *
     * @throws ExceptionInvalidValue - if $unitAmount is negative, or $productId is empty or a price's ID.
     */
    public function __construct(
        Currency $currency,
        string $productId,
        int $unitAmount,
        ?TaxBehavior $taxBehavior = null
    )
    {
        if ($unitAmount < 0)
        {
            throw new ExceptionInvalidValue("The unit amount must not be negative, but {$unitAmount} was given.");
        }

        StripeIds::checkProductId($productId);

        $this->m_currency = $currency;
        $this->m_productId = $productId;
        $this->m_unitAmount = $unitAmount;
        $this->m_taxBehavior = $taxBehavior;
    }


    /**
     * @return PriceDataForInvoiceItemArray
     */
    public function toArray() : array
    {
        $arrayForm = [
            'currency' => $this->m_currency->value,
            'product' => $this->m_productId,
            'unit_amount' => $this->m_unitAmount,
        ];

        if ($this->m_taxBehavior !== null)
        {
            $arrayForm['tax_behavior'] = $this->m_taxBehavior->value;
        }

        return $arrayForm;
    }
}
