<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\SubscriptionLineItem;

class SubscriptionLineItemCollection extends AbstractCollection
{
    public function __construct(SubscriptionLineItem ...$fields)
    {
        parent::__construct(SubscriptionLineItem::class, ...$fields);
    }


    public function toStripeArrayForm() : array
    {
        $nestedArrayForm = [];
        $arrayCopy = $this->getArrayCopy();

        foreach ($arrayCopy as $item)
        {
            /* @var $item \Programster\Stripe\Models\SubscriptionLineItem */
            $nestedArrayForm[] = $item->toArray();
        }

        return $nestedArrayForm;
    }
}