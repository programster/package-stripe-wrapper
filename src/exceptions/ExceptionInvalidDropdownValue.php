<?php

namespace Programster\Stripe\Exceptions;
class ExceptionInvalidDropdownValue extends \Exception
{
    public function __construct(private readonly string $value)
    {
        parent::__construct("Dropdown option value is not alphanumeric.");
    }

    public function getValue(): string
    {
        return $this->value;
    }
}