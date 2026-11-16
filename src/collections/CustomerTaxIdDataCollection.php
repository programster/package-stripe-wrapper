<?php

/*
 * A collection of a customer's tax IDs.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Models\CustomerTaxIdData;


/**
 * @extends AbstractReadonlyCollection<CustomerTaxIdData>
 */
final readonly class CustomerTaxIdDataCollection extends AbstractReadonlyCollection
{
    public function __construct(CustomerTaxIdData ...$taxIds)
    {
        parent::__construct(...$taxIds);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return list<array{type: string, value: string}>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this as $item)
        {
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }
}
