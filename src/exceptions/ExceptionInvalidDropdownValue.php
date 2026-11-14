<?php

/*
 * Thrown when a dropdown option's value isn't alphanumeric, which Stripe requires.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionInvalidDropdownValue extends \Exception
{
    private readonly string $m_value;


    /**
     * @param string $value - the value that isn't alphanumeric.
     */
    public function __construct(string $value)
    {
        $this->m_value = $value;

        parent::__construct(
            "Dropdown option value '{$value}' is not valid. It must be alphanumeric (only the letters a-z and A-Z, " .
            "and the digits 0-9)."
        );
    }


    # Accessors
    public function getValue() : string { return $this->m_value; }
}
