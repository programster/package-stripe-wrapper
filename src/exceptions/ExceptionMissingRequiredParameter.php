<?php

/*
 * Thrown when something that must be given wasn't, e.g. when an object needs at least one of its optional parameters.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionMissingRequiredParameter extends \Exception
{
    /**
     * @param string $message - what was missing.
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
