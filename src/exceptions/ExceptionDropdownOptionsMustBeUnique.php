<?php

namespace Programster\Stripe\Enums;
class ExceptionDropdownOptionsMustBeUnique extends \Exception
{
    public function __construct(private readonly string $value)
    {
        parent::__construct("Values within a dropdown field need to be unique.");
    }

    public function getValue(): string
    {
        return $this->value;
    }
}