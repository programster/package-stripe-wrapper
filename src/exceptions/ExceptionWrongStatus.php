<?php

/*
 * Thrown when an object isn't in the status a method needs, e.g. toCompleted() on a checkout session that hasn't
 * been completed. Check its is...() method first, e.g. isComplete().
 */

namespace Programster\Stripe\Exceptions;


class ExceptionWrongStatus extends \Exception
{
}
