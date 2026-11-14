<?php

/*
 * Thrown when a metadata key contains square brackets, which Stripe doesn't allow.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionInvalidKeyName extends \Exception
{
    private readonly string $m_keyName;


    /**
     * @param string $keyName - the key that contains square brackets.
     */
    public function __construct(string $keyName)
    {
        $this->m_keyName = $keyName;
        parent::__construct("A metadata key cannot contain square brackets, but '{$keyName}' does.");
    }


    # Accessors
    public function getKeyName() : string { return $this->m_keyName; }
}
