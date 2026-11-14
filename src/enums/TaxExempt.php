<?php

/*
 * A customer's tax exemption status.
 * https://docs.stripe.com/api/customers/create#create_customer-tax_exempt
 */

namespace Programster\Stripe\Enums;


enum TaxExempt: string
{
    case EXEMPT = "exempt";

    case NONE = "none";

    case REVERSE = "reverse";
}
