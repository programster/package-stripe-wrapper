<?php

/*
 * The varioius statuses that can be used for an invoice.
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