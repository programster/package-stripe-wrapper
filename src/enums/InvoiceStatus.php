<?php

/*
 * The statuses an invoice can have, e.g. for filtering a list of invoices.
 * https://docs.stripe.com/api/invoices/list#list_invoices-status
 */

namespace Programster\Stripe\Enums;


enum InvoiceStatus: string
{
    case DRAFT = "draft";
    case OPEN = "open";
    case PAID = "paid";
    case UNCOLLECTIBLE = "uncollectible";
    case VOID = "void";
}
