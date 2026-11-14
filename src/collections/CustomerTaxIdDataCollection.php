<?php

/*
 * A collection of a customer's tax IDs.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\CustomerTaxIdData;


class CustomerTaxIdDataCollection extends AbstractCollection
{
    public function __construct(CustomerTaxIdData ...$taxIds)
    {
        parent::__construct(CustomerTaxIdData::class, ...$taxIds);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return list<array<string, mixed>>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this->getArrayCopy() as $item)
        {
            /* @var $item CustomerTaxIdData */
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }
}
