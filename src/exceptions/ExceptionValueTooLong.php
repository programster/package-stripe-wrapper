<?php

/*
 * Thrown when a value is longer than Stripe allows.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionValueTooLong extends \Exception
{
    private readonly string $m_value;


    /**
     * @param string $value - the value that is too long.
     * @param string $message - optionally, what the limit is.
     */
    public function __construct(string $value, string $message = "Value provided is too long.")
    {
        $this->m_value = $value;
        parent::__construct($message);
    }


    # Accessors
    public function getValue() : string { return $this->m_value; }
}
