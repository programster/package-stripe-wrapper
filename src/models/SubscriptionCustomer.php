<?php

/*
 * Who a subscription created with createSubscription() is for: a customer, or a customer account (an account that's
 * also a customer, with Stripe's Accounts v2). Create one with customer() or customerAccount().
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-customer
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Internal\StripeIds;


final readonly class SubscriptionCustomer
{
    /** @var array<string, string> */
    private array $m_stripeParams;


    /**
     * This is private, so use customer() or customerAccount().
     * @param array<string, string> $stripeParams - the subscription's parameters for the customer.
     */
    private function __construct(array $stripeParams)
    {
        $this->m_stripeParams = $stripeParams;
    }


    /**
     * The subscription is for a customer.
     * @param string $customerId - the customer's ID, e.g. "cus_123".
     * @return SubscriptionCustomer
     * @throws ExceptionInvalidValue - if $customerId isn't a customer's ID.
     */
    public static function customer(string $customerId) : SubscriptionCustomer
    {
        StripeIds::checkCustomerId($customerId);
        return new SubscriptionCustomer(['customer' => $customerId]);
    }


    /**
     * The subscription is for a customer account.
     * @param string $accountId - the account's ID, e.g. "acct_123".
     * @return SubscriptionCustomer
     * @throws ExceptionInvalidValue - if $accountId isn't an account's ID.
     */
    public static function customerAccount(string $accountId) : SubscriptionCustomer
    {
        StripeIds::checkAccountId($accountId);
        return new SubscriptionCustomer(['customer_account' => $accountId]);
    }


    /**
     * Get the parameters to add to a subscription for this customer.
     * @return array<string, string>
     */
    public function getStripeParams() : array
    {
        return $this->m_stripeParams;
    }
}
