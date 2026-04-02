<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidKeyName;
use Programster\Stripe\Exceptions\ExceptionValueTooLong;
use Programster\Stripe\Interfaces\Arrayable;

class MetadataItem
{
    public function __construct(private readonly string $key, private readonly string $value)
    {
        if (strlen($value) > 500)
        {
            throw new ExceptionValueTooLong($this->value);
        }

        if (str_contains($key, "[") || str_contains($key, "]"))
        {
            throw new ExceptionInvalidKeyName($this->$key);
        }
    }


    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}