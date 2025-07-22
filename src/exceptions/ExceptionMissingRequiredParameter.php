<?php

namespace Programster\Stripe\Enums;
class ExceptionMissingRequiredParameter extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}