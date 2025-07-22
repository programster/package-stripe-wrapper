<?php

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Enums\ExceptionInvalidValue;
use Programster\Stripe\Models\MetadataItem;

class Metadata extends AbstractCollection
{
    public function __construct(MetadataItem ...$fields)
    {
        $existingKeys = [];

        foreach ($fields as $customField)
        {
            if (array_key_exists($customField->getKey(), $existingKeys))
            {
                throw new ExceptionInvalidValue($customField->getKey());
            }

            $existingKeys[$customField->getKey()] = 1;
        }

        parent::__construct(MetadataItem::class, ...$fields);
    }


    public function toStripeArrayForm() : array
    {
        $nestedArrayForm = [];
        $arrayCopy = $this->getArrayCopy();

        foreach ($arrayCopy as $item)
        {
            /* @var $item \Programster\Stripe\Models\MetadataItem */
            $nestedArrayForm[$item->getKey()] = $item->getValue();
        }

        return $nestedArrayForm;
    }
}