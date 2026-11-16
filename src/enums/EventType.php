<?php

/*
 * The types of webhook event for the objects this package has models for, to check an Event's type against with
 * Event::hasType(), so the comparison can't be misspelt. Stripe has many more types than these, and adds new ones, so
 * Event::getType() returns the type as Stripe sent it, whatever it is.
 * https://docs.stripe.com/api/events/types
 */

namespace Programster\Stripe\Enums;


enum EventType: string
{
    case CHECKOUT_SESSION_COMPLETED = "checkout.session.completed";
    case CHECKOUT_SESSION_ASYNC_PAYMENT_SUCCEEDED = "checkout.session.async_payment_succeeded";
    case CHECKOUT_SESSION_ASYNC_PAYMENT_FAILED = "checkout.session.async_payment_failed";
    case CHECKOUT_SESSION_EXPIRED = "checkout.session.expired";

    case CUSTOMER_CREATED = "customer.created";
    case CUSTOMER_UPDATED = "customer.updated";
    case CUSTOMER_DELETED = "customer.deleted";

    case CUSTOMER_SUBSCRIPTION_CREATED = "customer.subscription.created";
    case CUSTOMER_SUBSCRIPTION_UPDATED = "customer.subscription.updated";
    case CUSTOMER_SUBSCRIPTION_DELETED = "customer.subscription.deleted";
    case CUSTOMER_SUBSCRIPTION_PAUSED = "customer.subscription.paused";
    case CUSTOMER_SUBSCRIPTION_RESUMED = "customer.subscription.resumed";
    case CUSTOMER_SUBSCRIPTION_TRIAL_WILL_END = "customer.subscription.trial_will_end";
    case CUSTOMER_SUBSCRIPTION_PENDING_UPDATE_APPLIED = "customer.subscription.pending_update_applied";
    case CUSTOMER_SUBSCRIPTION_PENDING_UPDATE_EXPIRED = "customer.subscription.pending_update_expired";

    case INVOICE_CREATED = "invoice.created";
    case INVOICE_UPDATED = "invoice.updated";
    case INVOICE_DELETED = "invoice.deleted";
    case INVOICE_FINALIZED = "invoice.finalized";
    case INVOICE_FINALIZATION_FAILED = "invoice.finalization_failed";
    case INVOICE_SENT = "invoice.sent";
    case INVOICE_UPCOMING = "invoice.upcoming";
    case INVOICE_PAID = "invoice.paid";
    case INVOICE_PAYMENT_SUCCEEDED = "invoice.payment_succeeded";
    case INVOICE_PAYMENT_FAILED = "invoice.payment_failed";
    case INVOICE_PAYMENT_ACTION_REQUIRED = "invoice.payment_action_required";
    case INVOICE_OVERDUE = "invoice.overdue";
    case INVOICE_WILL_BE_DUE = "invoice.will_be_due";
    case INVOICE_MARKED_UNCOLLECTIBLE = "invoice.marked_uncollectible";
    case INVOICE_VOIDED = "invoice.voided";

    case PAYMENT_INTENT_CREATED = "payment_intent.created";
    case PAYMENT_INTENT_PROCESSING = "payment_intent.processing";
    case PAYMENT_INTENT_REQUIRES_ACTION = "payment_intent.requires_action";
    case PAYMENT_INTENT_AMOUNT_CAPTURABLE_UPDATED = "payment_intent.amount_capturable_updated";
    case PAYMENT_INTENT_PARTIALLY_FUNDED = "payment_intent.partially_funded";
    case PAYMENT_INTENT_SUCCEEDED = "payment_intent.succeeded";
    case PAYMENT_INTENT_PAYMENT_FAILED = "payment_intent.payment_failed";
    case PAYMENT_INTENT_CANCELED = "payment_intent.canceled";

    case CHARGE_PENDING = "charge.pending";
    case CHARGE_SUCCEEDED = "charge.succeeded";
    case CHARGE_FAILED = "charge.failed";
    case CHARGE_CAPTURED = "charge.captured";
    case CHARGE_EXPIRED = "charge.expired";
    case CHARGE_UPDATED = "charge.updated";
    case CHARGE_REFUNDED = "charge.refunded";

    case REFUND_CREATED = "refund.created";
    case REFUND_UPDATED = "refund.updated";
    case REFUND_FAILED = "refund.failed";

    case PAYMENT_METHOD_ATTACHED = "payment_method.attached";
    case PAYMENT_METHOD_DETACHED = "payment_method.detached";
    case PAYMENT_METHOD_UPDATED = "payment_method.updated";
    case PAYMENT_METHOD_AUTOMATICALLY_UPDATED = "payment_method.automatically_updated";
}
