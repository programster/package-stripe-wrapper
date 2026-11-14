<?php

/*
 * Thrown when the same key is given more than once, e.g. in a set of metadata.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionDuplicateKey extends ExceptionInvalidValue
{
    private readonly string $m_key;


    /**
     * @param string $key - the key that was given more than once.
     */
    public function __construct(string $key)
    {
        $this->m_key = $key;
        parent::__construct("You provided a duplicate key '{$key}'.");
    }


    # Accessors
    public function getKey() : string { return $this->m_key; }
}
