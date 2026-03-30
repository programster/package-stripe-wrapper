<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\TaxBehavior;
use Programster\Stripe\Interfaces\Arrayable;

readonly class PriceDataForInvoiceItem implements Arrayable
{
    /**
     * @param Currency $currency
     * @param string $productId - The ID Of the product this price will belong to.
     * @param int $unitAmount - A non-negative integer in cents representing how much to charge. E.g. 500 for 5 USD.
     * @param TaxBehavior|null $taxBehavior
     */
    public function __construct(
        private Currency $currency,
        private string $productId,
        private int $unitAmount,
        private ?TaxBehavior $taxBehavior = null
    )
    {

    }


    public function toArray(): array
    {
        $arrayForm = [
            'currency' => $this->currency->value,
            'product' => $this->productId,
            'unit_amount' => $this->unitAmount,
        ];

        if ($this->taxBehavior !== null)
        {
            $arrayForm['tax_behavior'] = $this->taxBehavior->value;
        }

        return $arrayForm;
    }
}