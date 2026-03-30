<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\SubscriptionInvoiceItem;
use Programster\Stripe\Models\SubscriptionLineItem;

class SubscriptionInvoiceItemCollection extends AbstractCollection
{
    public function __construct(SubscriptionInvoiceItem ...$fields)
    {
        parent::__construct(SubscriptionInvoiceItem::class, ...$fields);
    }


    public function toStripeArrayForm() : array
    {
        $nestedArrayForm = [];
        $arrayCopy = $this->getArrayCopy();

        foreach ($arrayCopy as $item)
        {
            /* @var $item SubscriptionInvoiceItem */
            $nestedArrayForm[] = $item->toArray();
        }

        return $nestedArrayForm;
    }
}