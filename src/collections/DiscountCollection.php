<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\SubscriptionItemDiscount;

class DiscountCollection extends AbstractCollection
{
    public function __construct(SubscriptionItemDiscount ...$discounts)
    {
        parent::__construct(SubscriptionItemDiscount::class, ...$discounts);
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