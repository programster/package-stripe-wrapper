<?php

/*
 * A set of key-value pairs to attach to a Stripe object. Each key can only be used once, and Stripe allows up to 50.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractCollection;
use Programster\Stripe\Exceptions\ExceptionDuplicateKey;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Models\MetadataItem;


class Metadata extends AbstractCollection
{
    /**
     * @param MetadataItem ...$fields - the key-value pairs. Each key can only be used once.
     * @throws ExceptionDuplicateKey - if a key is used more than once.
     * @throws ExceptionInvalidValue - if there are more than 50 pairs.
     */
    public function __construct(MetadataItem ...$fields)
    {
        $existingKeys = [];

        foreach ($fields as $field)
        {
            if (array_key_exists($field->getKey(), $existingKeys))
            {
                throw new ExceptionDuplicateKey($field->getKey());
            }

            $existingKeys[$field->getKey()] = true;
        }

        $numFields = count($fields);

        if ($numFields > 50)
        {
            throw new ExceptionInvalidValue("Stripe allows up to 50 metadata keys, but {$numFields} were given.");
        }

        parent::__construct(MetadataItem::class, ...$fields);
    }


    /**
     * Get the metadata in the form Stripe takes: an array of each value, keyed by its key.
     * @return array<string, string>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this->getArrayCopy() as $item)
        {
            /* @var $item MetadataItem */
            $stripeArrayForm[$item->getKey()] = $item->getValue();
        }

        return $stripeArrayForm;
    }
}
