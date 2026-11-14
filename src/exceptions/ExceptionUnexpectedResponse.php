<?php

/*
 * Thrown when Stripe's response is missing a field the package needs, or has one of an unexpected type, e.g.
 * because Stripe's API has changed in a way the package doesn't know about yet.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionUnexpectedResponse extends \Exception
{
}
