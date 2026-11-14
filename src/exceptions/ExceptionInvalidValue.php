<?php

/*
 * Thrown when a value is given that Stripe wouldn't accept, so that the mistake is caught where it was made.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionInvalidValue extends \Exception
{
}
