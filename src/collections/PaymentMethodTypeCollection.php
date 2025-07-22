<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Enums\PaymentMethodType;
use Programster\Stripe\Models\CustomField;
use Programster\Stripe\Models\SubscriptionLineItem;

class PaymentMethodTypeCollection extends AbstractCollection
{
    public function __construct(PaymentMethodType ...$fields)
    {
        parent::__construct(PaymentMethodType::class, ...$fields);
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
            /* @var $item \Programster\Stripe\Enums\PaymentMethodType */
            $nestedArrayForm[] = $item->value;
        }

        return $nestedArrayForm;
    }
}