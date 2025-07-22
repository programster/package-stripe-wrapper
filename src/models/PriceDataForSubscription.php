<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\TaxBehavior;
use Programster\Stripe\Interfaces\Arrayable;

readonly class PriceDataForSubscription implements Arrayable
{
    /**
     * @param Currency $currency
     * @param int $unitAmount - A non-negative integer in cents representing how much to charge. E.g. 500 for 5 USD.
     * @param ProductData|string $productDataOrId - either the ID of an already registered product in Stipe, or the
     * data to create a new inline product.
     */
    public function __construct(
        private Currency $currency,
        private int $unitAmount,
        private ProductData|string $productDataOrId,
        private ?RecurringConfig $recurring = null,
        private ?TaxBehavior $taxBehavior = null
    )
    {

    }

    public function toArray(): array
    {
        $arrayForm = [
            'currency' => $this->currency->value,
            'unit_amount' => $this->unitAmount,
        ];

        if (is_string($this->productDataOrId))
        {
            // user provided product ID string
            $arrayForm['product'] = $this->productDataOrId;
        }
        else
        {
            $arrayForm['product_data'] = $this->productDataOrId->toArray();
        }

        if ($this->recurring !== null)
        {
            $arrayForm['recurring'] = $this->recurring->toArray();
        }

        if ($this->taxBehavior !== null)
        {
            $arrayForm['tax_behavior'] = $this->taxBehavior->value;
        }

        return $arrayForm;
    }
}