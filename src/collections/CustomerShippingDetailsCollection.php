<?php

/*
 * A collection of a customer's shipping details.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\CustomerShippingDetails;


class CustomerShippingDetailsCollection extends AbstractCollection
{
    public function __construct(CustomerShippingDetails ...$addresses)
    {
        parent::__construct(CustomerShippingDetails::class, ...$addresses);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's array forms.
     * @return array
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this->getArrayCopy() as $item)
        {
            /* @var $item CustomerShippingDetails */
            $stripeArrayForm[] = $item->toArray();
        }

        return $stripeArrayForm;
    }
}
