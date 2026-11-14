<?php

/*
 * A collection of the consent levels a saved payment method may have been saved with, for it to be shown again.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Enums\AllowRedisplayFilters;


class AllowRedisplayFiltersCollection extends AbstractCollection
{
    public function __construct(AllowRedisplayFilters ...$filters)
    {
        parent::__construct(AllowRedisplayFilters::class, ...$filters);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's values.
     * @return list<string>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this->getArrayCopy() as $item)
        {
            /* @var $item AllowRedisplayFilters */
            $stripeArrayForm[] = $item->value;
        }

        return $stripeArrayForm;
    }
}
