<?php

/*
 * Checks on the IDs given to the request models, so an ID of the wrong kind is caught where it's given, rather than
 * when Stripe rejects the request. Prices (through legacy plans) and products can have IDs of your own choosing, so
 * those are only checked for being empty, or for being the ID of the other one. This is internal to the package, and
 * may change in any release.
 */

namespace Programster\Stripe\Internal;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;

/**
 * @internal
 */
final readonly class StripeIds
{
    private function __construct()
    {
    }


    /**
     * Check an ID is a customer's, which starts "cus_".
     * @param string $id - the ID to check.
     * @return void
     * @throws ExceptionInvalidValue - if it isn't a customer's ID.
     */
    public static function checkCustomerId(string $id) : void
    {
        if (!str_starts_with($id, 'cus_'))
        {
            throw new ExceptionInvalidValue("A customer's ID starts \"cus_\", but \"{$id}\" was given.");
        }
    }


    /**
     * Check an ID is an account's, which starts "acct_".
     * @param string $id - the ID to check.
     * @return void
     * @throws ExceptionInvalidValue - if it isn't an account's ID.
     */
    public static function checkAccountId(string $id) : void
    {
        if (!str_starts_with($id, 'acct_'))
        {
            throw new ExceptionInvalidValue("An account's ID starts \"acct_\", but \"{$id}\" was given.");
        }
    }


    /**
     * Check an ID could be a price's (or a legacy plan's): it isn't empty, and isn't a product's.
     * @param string $id - the ID to check.
     * @return void
     * @throws ExceptionInvalidValue - if it's empty, or a product's ID.
     */
    public static function checkPriceId(string $id) : void
    {
        if ($id === '')
        {
            throw new ExceptionInvalidValue("A price's ID can't be empty.");
        }

        if (str_starts_with($id, 'prod_'))
        {
            throw new ExceptionInvalidValue("\"{$id}\" is a product's ID, but a price's ID is needed.");
        }
    }


    /**
     * Check an ID could be a product's: it isn't empty, and isn't a price's.
     * @param string $id - the ID to check.
     * @return void
     * @throws ExceptionInvalidValue - if it's empty, or a price's ID.
     */
    public static function checkProductId(string $id) : void
    {
        if ($id === '')
        {
            throw new ExceptionInvalidValue("A product's ID can't be empty.");
        }

        if (str_starts_with($id, 'price_'))
        {
            throw new ExceptionInvalidValue("\"{$id}\" is a price's ID, but a product's ID is needed.");
        }
    }
}
