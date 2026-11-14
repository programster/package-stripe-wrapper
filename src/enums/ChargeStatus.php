<?php

/*
 * The status of a charge.
 * https://docs.stripe.com/api/charges/object#charge_object-status
 */

namespace Programster\Stripe\Enums;

enum ChargeStatus: string
{
    case SUCCEEDED = "succeeded";
    case PENDING = "pending";
    case FAILED = "failed";
}
