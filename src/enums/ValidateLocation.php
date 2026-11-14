<?php

/*
 * When Stripe should validate a customer's tax location.
 * https://docs.stripe.com/api/customers/create?lang=php#create_customer-tax-validate_location
 */

namespace Programster\Stripe\Enums;


enum ValidateLocation: string
{
    case DEFERRED = "deferred";
    case IMMEDIATELY = "immediately";
}
