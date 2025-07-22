<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Interfaces\Arrayable;

readonly class PriceDataForSinglePayment implements Arrayable
{
    /**
     * @param Currency $currency - the currency to charge the item in.
     * @param int $unitAmount - A non-negative integer in cents representing how much to charge.
     * @param ProductData|string $productDataOrId - either the ID of an already registered product in Stipe, or the
     * data to create a new inline product.
     */
    public function __construct(
        private Currency $currency,
        private int $unitAmount,
        private ProductData|string $productDataOrId,
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
            $arrayForm['product'] = $this->productDataOrId;
        }
        else
        {
            $arrayForm['product_data'] = $this->productDataOrId->toArray();
        }

        return $arrayForm;
    }
}