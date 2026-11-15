<?php

/*
 * Who a subscription created with createSubscription() is for: a customer, or a customer account (an account that's
 * also a customer, with Stripe's Accounts v2). Create one with createUsingCustomerId(),
 * createUsingCustomer() or createUsingCustomerAccountId().
 * https://docs.stripe.com/api/subscriptions/create#create_subscription-customer
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Internal\StripeIds;
use Programster\Stripe\Responses\Customer;


/**
 * @phpstan-type CustomerParams array{customer: string}|array{customer_account: string}
 */
final readonly class SubscriptionCustomer
{
    /** @var CustomerParams */
    private array $m_stripeParams;


    /**
     * This is private, so use createUsingCustomerId(), createUsingCustomer() or createUsingCustomerAccountId().
     * @param CustomerParams $stripeParams - the subscription's parameters for the customer.
     */
    private function __construct(array $stripeParams)
    {
        $this->m_stripeParams = $stripeParams;
    }


    /**
     * The subscription is for a customer, by its ID.
     * @param string $customerId - the customer's ID, e.g. "cus_123".
     * @return SubscriptionCustomer
     * @throws ExceptionInvalidValue - if $customerId isn't a customer's ID.
     */
    public static function createUsingCustomerId(string $customerId) : SubscriptionCustomer
    {
        StripeIds::checkCustomerId($customerId);
        return new SubscriptionCustomer(['customer' => $customerId]);
    }


    /**
     * The subscription is for a customer, such as one just retrieved or created. Only its ID is used, so it must be in
     * the same Stripe account as the client the subscription is created with.
     *
     * @param Customer $customer - the customer.
     *
     * @return SubscriptionCustomer
     */
    public static function createUsingCustomer(Customer $customer) : SubscriptionCustomer
    {
        return self::createUsingCustomerId($customer->getId());
    }


    /**
     * The subscription is for a customer account.
     * @param string $accountId - the account's ID, e.g. "acct_123".
     * @return SubscriptionCustomer
     * @throws ExceptionInvalidValue - if $accountId isn't an account's ID.
     */
    public static function createUsingCustomerAccountId(string $accountId) : SubscriptionCustomer
    {
        StripeIds::checkAccountId($accountId);
        return new SubscriptionCustomer(['customer_account' => $accountId]);
    }


    /**
     * Get the parameters to add to a subscription for this customer.
     * @return CustomerParams
     */
    public function getStripeParams() : array
    {
        return $this->m_stripeParams;
    }
}
