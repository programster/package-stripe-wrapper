<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Enums\CountryCode;
use Programster\Stripe\Enums\Locale;
use Programster\Stripe\Models\CustomerShippingDetails;

class CustomerShippingDetailsCollection extends AbstractCollection
{
    public function __construct(CustomerShippingDetails ...$addresses)
    {
        parent::__construct(CustomerShippingDetails::class, ...$addresses);
    }


    /**
     * Return the PaymentMethodTypeCollection in a collapsed array form suitable for sending to stripe.
     * @return array
     */
    public function toStripeArrayForm() : array
    {
        $nestedArrayForm = [];
        $arrayCopy = $this->getArrayCopy();

        foreach ($arrayCopy as $item)
        {
            /* @var $item CustomerShippingDetails */
            $nestedArrayForm[] = $item->toArray();
        }

        return $nestedArrayForm;
    }
}