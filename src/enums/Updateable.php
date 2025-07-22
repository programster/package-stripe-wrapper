<?php

/*
 * An enum for specifying if a customer field can be updated.
 */

namespace Programster\Stripe\Enums;

enum Updatable: string
{
    // Checkout will automatically determine whether to update the provided Customer object using details from the session.
    case AUTO = "auto";

    // Checkout will never update the provided Customer object.
    case NEVER = "never";
}