<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Enums\CountryCode;
use Programster\Stripe\Enums\Locale;

class LocaleCollection extends AbstractCollection
{
    public function __construct(Locale ...$countryCodes)
    {
        parent::__construct(Locale::class, ...$countryCodes);
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
            /* @var $item \Programster\Stripe\Enums\CountryCode */
            $nestedArrayForm[] = $item->value;
        }

        return $nestedArrayForm;
    }
}