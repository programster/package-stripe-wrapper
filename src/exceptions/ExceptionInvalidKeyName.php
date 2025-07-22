<?php

namespace Programster\Stripe\Enums;
class ExceptionInvalidKeyName extends \Exception
{
    public function __construct(private readonly string $keyName)
    {
        parent::__construct("A metadata key cannot contain square brackets.");
    }

    public function getKeyName(): string
    {
        return $this->keyName;
    }
}