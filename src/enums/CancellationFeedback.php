<?php

/*
 * Enum of all the currencies Stripe accepts.
 * https://docs.stripe.com/currencies
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