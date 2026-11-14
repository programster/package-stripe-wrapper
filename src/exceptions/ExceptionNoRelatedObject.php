<?php

/*
 * Thrown when a fetch...() method is called for a related object that isn't there, e.g. fetchCustomer() on a checkout
 * session that has no customer. Check the matching get...Id() method first, which returns null when there's none.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionNoRelatedObject extends \Exception
{
}
