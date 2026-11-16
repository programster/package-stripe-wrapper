<?php

/*
 * A collection of payment method types, e.g. for the ways a customer may pay at checkout.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Enums\PaymentMethodType;


/**
 * @extends AbstractReadonlyCollection<PaymentMethodType>
 */
final readonly class PaymentMethodTypeCollection extends AbstractReadonlyCollection
{
    public function __construct(PaymentMethodType ...$fields)
    {
        parent::__construct(...$fields);
    }


    /**
     * Get the collection in the form Stripe takes: a list of each item's values.
     * @return list<string>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this as $item)
        {
            $stripeArrayForm[] = $item->value;
        }

        return $stripeArrayForm;
    }
}
