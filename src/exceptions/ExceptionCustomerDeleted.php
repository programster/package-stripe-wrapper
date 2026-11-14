<?php

/*
 * Thrown when a customer is asked for that has been deleted, as Stripe only gives back its ID.
 */

namespace Programster\Stripe\Exceptions;


class ExceptionCustomerDeleted extends \Exception
{
}
