<?php

/*
 * How a customer's cash balance is reconciled against their invoices.
 * https://docs.stripe.com/api/customers/create?lang=php#create_customer-cash_balance-settings-reconciliation_mode
 */

namespace Programster\Stripe\Enums;


enum ReconciliationMode: string
{
    case AUTOMATIC = "automatic";

    case MANUAL = "manual";

    case MERCHANT_DEFAULT = "merchant_default";
}
