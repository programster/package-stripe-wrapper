<?php

/*
 * The kind of transaction a checkout session is for, which decides the text on its submit button.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-submit_type
 */

namespace Programster\Stripe\Enums;


enum SubmitType: string
{
    // will use "pay" or "subcribe" based on the mode of the checkout session.
    case AUTO = "auto";

    // Recommended when offering bookings. Submit button includes a ‘Book’ label
    case BOOK = "book";

    // Recommended when accepting donations. Submit button includes a ‘Donate’ label
    case DONATE = "donate";

    // Submit button includes a ‘Buy’ label
    case PAY = "pay";

    // Submit button includes a ‘Subscribe’ label
    case SUBSCRIBE = "subscribe";
}
