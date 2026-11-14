<?php

/*
 * The kinds of custom field that can be added to a checkout session.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-custom_fields-type
 */

namespace Programster\Stripe\Enums;

enum CustomFieldType: string
{
    // A field that only accepts digits.
    case NUMERIC = "numeric";

    // A free text field.
    case TEXT = "text";

    // A list of options to choose from.
    case DROPDOWN = "dropdown";
}
