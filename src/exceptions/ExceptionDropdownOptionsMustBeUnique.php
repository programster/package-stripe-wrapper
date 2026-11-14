<?php

/*
 * Thrown when two options in a dropdown field have the same value.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionDropdownOptionsMustBeUnique extends \Exception
{
    private readonly string $m_value;


    /**
     * @param string $value - the value that more than one option had.
     */
    public function __construct(string $value)
    {
        $this->m_value = $value;
        parent::__construct("Values within a dropdown field must be unique, but '{$value}' was used more than once.");
    }


    # Accessors
    public function getValue() : string { return $this->m_value; }
}
