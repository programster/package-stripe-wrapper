<?php

/*
 * Enum of all the currencies Stripe accepts.
 * https://docs.stripe.com/currencies
 */

namespace Programster\Stripe\Enums;

enum RecurringInterval: string
{
    case DAY = "day";
    case MONTH = "month";
    case WEEK = "week";
    case YEAR = "year";
}