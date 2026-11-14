<?php

/*
 * Thrown when a list matches more results than its safety cap allows (1,000 by default), rather than quietly giving
 * back only some of them. Narrow the filters, give a limit (ResultLimit::upTo()), or ask for every result
 * (ResultLimit::all()).
 */

namespace Programster\Stripe\Exceptions;


class ExceptionTooManyResults extends \Exception
{
}
