<?php

/*
 * The modes a checkout session can be created in.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-mode
 */

namespace Programster\Stripe\Enums;


enum SessionMode: string
{
    /*
     * Accept one-time payments for cards, iDEAL, and more.
     */
    case PAYMENT = "payment";

    /*
     * Save payment details to charge your customers later.
     */
    case SETUP = "setup";

    /*
     * Use Stripe Billing to set up fixed-price subscriptions.
     */
    case SUBSCRIPTION = "subscription";
}
