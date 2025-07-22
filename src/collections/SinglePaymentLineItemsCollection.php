<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Models\SinglePaymentLineItem;

class SinglePaymentLineItemsCollection extends AbstractCollection
{
    public function __construct(SinglePaymentLineItem ...$fields)
    {
        parent::__construct(SinglePaymentLineItem::class, ...$fields);
    }


    public function toStripeArrayForm() : array
    {
        $nestedArrayForm = [];
        $arrayCopy = $this->getArrayCopy();

        foreach ($arrayCopy as $item)
        {
            /* @var $item \Programster\Stripe\Models\SinglePaymentLineItem */
            $nestedArrayForm[] = $item->toArray();
        }

        return $nestedArrayForm;
    }
}