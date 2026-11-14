<?php

/*
 * One key-value pair of metadata, to go in a Metadata collection.
 * https://docs.stripe.com/api/metadata
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidKeyName;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Exceptions\ExceptionValueTooLong;

class MetadataItem
{
    private readonly string $m_key;
    private readonly string $m_value;


    /**
     * Create a metadata item.
     *
     * @param string $key - the key, from 1 to 40 characters, which can't contain square brackets.
     *
     * @param string $value - the value, up to 500 characters.
     *
     * @throws ExceptionInvalidValue - if the key is empty, or more than 40 characters.
     * @throws ExceptionValueTooLong - if the value is more than 500 characters.
     * @throws ExceptionInvalidKeyName - if the key contains square brackets.
     */
    public function __construct(string $key, string $value)
    {
        if ($key === "" || strlen($key) > 40)
        {
            throw new ExceptionInvalidValue("A metadata key must be 1 to 40 characters long, but '{$key}' isn't.");
        }

        if (strlen($value) > 500)
        {
            throw new ExceptionValueTooLong($value);
        }

        if (str_contains($key, "[") || str_contains($key, "]"))
        {
            throw new ExceptionInvalidKeyName($key);
        }

        $this->m_key = $key;
        $this->m_value = $value;
    }


    # Accessors
    public function getKey() : string { return $this->m_key; }
    public function getValue() : string { return $this->m_value; }
}
