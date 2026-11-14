<?php

/*
 * A collection of payment method types, e.g. for the ways a customer may pay at checkout.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Enums\PaymentMethodType;


class PaymentMethodTypeCollection extends AbstractCollection
{
    public function __construct(PaymentMethodType ...$fields)
    {
        parent::__construct(PaymentMethodType::class, ...$fields);
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
            /* @var $item PaymentMethodType */
            $stripeArrayForm[] = $item->value;
        }

        return $stripeArrayForm;
    }
}
