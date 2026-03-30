<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\SubscriptionItem;

class SubscriptionLineItemCollection extends AbstractCollection
{
    public function __construct(SubscriptionItem ...$fields)
    {
        parent::__construct(SubscriptionItem::class, ...$fields);
    }


    public function toStripeArrayForm() : array
    {
        $nestedArrayForm = [];
        $arrayCopy = $this->getArrayCopy();

        foreach ($arrayCopy as $item)
        {
            /* @var $item SubscriptionItem */
            $nestedArrayForm[] = $item->toArray();
        }

        return $nestedArrayForm;
    }
}