<?php

/*
 * The reasons a customer can give for cancelling a subscription.
 * https://docs.stripe.com/api/subscriptions/cancel#cancel_subscription-cancellation_details-feedback
 */

namespace Programster\Stripe\Enums;


enum CancellationFeedback: string
{
    case CUSTOMER_SERVICE = "customer_service";
    case LOW_QUALITY = "low_quality";
    case MISSING_FEATURES = "missing_features";
    case OTHER = "other";
    case SWITCHED_SERVICE = "switched_service";
    case TOO_COMPLEX = "too_complex";
    case TOO_EXPENSIVE = "too_expensive";
    case UNUSED = "unused";
}
