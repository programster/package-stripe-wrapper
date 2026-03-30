<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\SubscriptionCheckoutLineItem;

class SubscriptionCheckoutLineItemCollection extends AbstractCollection
{
    public function __construct(SubscriptionCheckoutLineItem ...$fields)
    {
        parent::__construct(SubscriptionCheckoutLineItem::class, ...$fields);
    }


    public function toStripeArrayForm() : array
    {
        $nestedArrayForm = [];
        $arrayCopy = $this->getArrayCopy();

        foreach ($arrayCopy as $item)
        {
            /* @var $item \Programster\Stripe\Models\SubscriptionCheckoutLineItem */
            $nestedArrayForm[] = $item->toArray();
        }

        return $nestedArrayForm;
    }
}