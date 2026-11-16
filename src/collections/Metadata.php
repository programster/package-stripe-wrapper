<?php

/*
 * A set of key-value pairs to attach to a Stripe object. Each key can only be used once, and Stripe allows up to 50.
 */

namespace Programster\Stripe\Collections;

use Programster\Collections\AbstractReadonlyCollection;
use Programster\Stripe\Exceptions\ExceptionDuplicateKey;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Exceptions\ExceptionMissingMetadataKey;
use Programster\Stripe\Models\MetadataItem;


/**
 * @extends AbstractReadonlyCollection<MetadataItem>
 */
final readonly class Metadata extends AbstractReadonlyCollection
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

        parent::__construct(...$fields);
    }


    /**
     * Check whether the metadata has a key, e.g. before calling getValue() for one that may not be there.
     * @param string $key - the key, e.g. "order_id". Keys are case-sensitive, as they are in Stripe.
     * @return bool
     */
    public function hasKey(string $key) : bool
    {
        return !$this->where('getKey', $key)->isEmpty();
    }


    /**
     * Get the value for a key. Use hasKey() first if the key may not be there.
     * @param string $key - the key, e.g. "order_id". Keys are case-sensitive, as they are in Stripe.
     * @return string
     * @throws ExceptionMissingMetadataKey - if there's no such key.
     */
    public function getValue(string $key) : string
    {
        $matches = $this->where('getKey', $key);

        if ($matches->isEmpty())
        {
            throw new ExceptionMissingMetadataKey($key);
        }

        return $matches->first()->getValue();
    }


    /**
     * Get the metadata in the form Stripe takes: an array of each value, keyed by its key.
     * @return array<string, string>
     */
    public function toStripeArrayForm() : array
    {
        $stripeArrayForm = [];

        foreach ($this as $item)
        {
            $stripeArrayForm[$item->getKey()] = $item->getValue();
        }

        return $stripeArrayForm;
    }


    public function getType(): string { return MetadataItem::class; }
}
