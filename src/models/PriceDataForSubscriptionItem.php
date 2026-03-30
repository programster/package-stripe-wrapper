<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\TaxBehavior;
use Programster\Stripe\Interfaces\Arrayable;

readonly class PriceDataForSubscriptionItem implements Arrayable
{
    /**
     * @param Currency $currency
     *
     * @param int $unitPriceAmount - A non-negative integer in cents representing how much to charge. E.g. 500 for 5 USD.
     *
     * @param string $productId - the ID of the product this price data is for.
     *
     * @param RecurringConfig $recurring
     *
     * @param TaxBehavior|null $taxBehavior
     */
    public function __construct(
        private Currency        $currency,
        private int             $unitPriceAmount,
        private string          $productId,
        private RecurringConfig $recurring,
        private ?TaxBehavior    $taxBehavior = null
    )
    {

    }

    public function toArray(): array
    {
        $arrayForm = [
            'currency' => $this->currency->value,
            'unit_amount' => $this->unitPriceAmount,
            'product' => $this->productId,
            'recurring' => $this->recurring->toArray(),
        ];

        if ($this->taxBehavior !== null)
        {
            $arrayForm['tax_behavior'] = $this->taxBehavior->value;
        }

        return $arrayForm;
    }
}