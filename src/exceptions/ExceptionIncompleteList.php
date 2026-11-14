<?php

/*
 * Thrown when a list Stripe included in a response is incomplete (Stripe only includes the first few), rather than
 * quietly giving back only some of it. Use the matching fetch...() method, which gets all of them.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionIncompleteList extends \Exception
{
}
