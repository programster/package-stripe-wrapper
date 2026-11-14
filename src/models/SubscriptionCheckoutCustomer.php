<?php

/*
 * Who a checkout session for a subscription is for: an existing customer, or an email address to fill in for a new
 * one. Stripe always creates a customer for a new subscriber, so unlike SinglePaymentCheckoutCustomer, there's no
 * choice about that. Create one with one of the static creators.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-customer
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Internal\StripeIds;


final readonly class SubscriptionCheckoutCustomer
{
    /** @var array<string, mixed> */
    private array $m_stripeParams;


    /**
     * This is private, so use existing() or withEmail().
     * @param array<string, mixed> $stripeParams - the checkout session's parameters for the customer.
     */
    private function __construct(array $stripeParams)
    {
        $this->m_stripeParams = $stripeParams;
    }


    /**
     * The session is for an existing customer.
     *
     * @param string $customerId - the customer's ID, e.g. "cus_123".
     *
     * @param CustomerUpdateConfig|null $customerUpdateConfig - optionally, which of the customer's details the session
     * may update with what the customer enters.
     *
     * @return SubscriptionCheckoutCustomer
     *
     * @throws ExceptionInvalidValue - if $customerId isn't a customer's ID.
     */
    public static function existing(
        string $customerId,
        ?CustomerUpdateConfig $customerUpdateConfig = null
    ) : SubscriptionCheckoutCustomer
    {
        StripeIds::checkCustomerId($customerId);
        $params = ['customer' => $customerId];

        if ($customerUpdateConfig !== null)
        {
            $params['customer_update'] = $customerUpdateConfig->toArray();
        }

        return new SubscriptionCheckoutCustomer($params);
    }


    /**
     * The session is for someone who isn't a customer yet, whose email address is filled in for them.
     * @param string $email - the email address.
     * @return SubscriptionCheckoutCustomer
     * @throws ExceptionInvalidValue - if $email isn't an email address.
     */
    public static function withEmail(string $email) : SubscriptionCheckoutCustomer
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
        {
            throw new ExceptionInvalidValue("\"{$email}\" isn't an email address.");
        }

        return new SubscriptionCheckoutCustomer(['customer_email' => $email]);
    }


    /**
     * Get the parameters to add to a checkout session for this customer.
     * @return array<string, mixed>
     */
    public function getStripeParams() : array
    {
        return $this->m_stripeParams;
    }
}
