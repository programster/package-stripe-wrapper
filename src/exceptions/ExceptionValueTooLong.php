<?php

namespace Programster\Stripe\Exceptions;

class ExceptionValueTooLong extends \Exception
{
    public function __construct(private readonly string $value, string $message = "Value provided is too long.")
    {
        parent::__construct($this->message);
    }

    public function getValue(): string { return $this->value; }
}