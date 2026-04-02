<?php

namespace Programster\Stripe\Exceptions;
class ExceptionMissingRequiredParameter extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}