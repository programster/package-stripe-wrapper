<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Enums\AllowRedisplayFilters;
use Programster\Stripe\Enums\CountryCode;

class AllowRedisplayFiltersCollection extends AbstractCollection
{
    public function __construct(AllowRedisplayFilters ...$filters)
    {
        parent::__construct(AllowRedisplayFilters::class, ...$filters);
    }


    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];
        $arrayCopy = $this->getArrayCopy();

        foreach ($arrayCopy as $item)
        {
            /* @var $item \Programster\Stripe\Enums\AllowRedisplayFilters */
            $stripeArrayForm[] = $item->value;
        }

        return $stripeArrayForm;
    }
}