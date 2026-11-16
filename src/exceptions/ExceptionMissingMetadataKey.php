<?php

/*
 * Thrown when asking a set of metadata for the value of a key it doesn't have. Check with Metadata::hasKey() first if
 * the key may not be there.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionMissingMetadataKey extends \Exception
{
    private readonly string $m_key;


    /**
     * @param string $key - the key that isn't there.
     */
    public function __construct(string $key)
    {
        $this->m_key = $key;
        parent::__construct("The metadata has no key '{$key}'. Check with hasKey() first if it may not be there.");
    }


    # Accessors
    public function getKey() : string { return $this->m_key; }
}
