<?php

/*
 * The status of a refund.
 * https://docs.stripe.com/api/refunds/object#refund_object-status
 */

namespace Programster\Stripe\Enums;

enum RefundStatus: string
{
    case PENDING = "pending";
    case REQUIRES_ACTION = "requires_action";
    case SUCCEEDED = "succeeded";
    case FAILED = "failed";
    case CANCELED = "canceled";
}
