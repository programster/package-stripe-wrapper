<?php


namespace Programster\Stripe\Enums;

enum TaxExempt: string
{
    case EXEMPT = "exempt";

    case NONE = "none";

    case REVERSE = "reverse";
}