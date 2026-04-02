<?php

namespace Programster\Stripe\Exceptions;
class ExceptionDuplicateKey extends \Exception
{
    public function __construct(private readonly string $key)
    {
        parent::__construct("You provided a duplicate key '$key'.");
    }

    public function getKey(): string
    {
        return $this->key;
    }
}