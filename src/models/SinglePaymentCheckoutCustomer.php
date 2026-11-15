<?php

/*
 * Who a checkout session for a single payment is for: an existing customer, an email address to fill in for a new
 * one, or neither, with whether Stripe should create a customer. Create one with one of the static creators, which
 * only take what Stripe accepts together, e.g. a customer update config only goes with an existing customer.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-customer
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\CustomerCreation;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Internal\StripeIds;
use Programster\Stripe\Responses\Customer;


/**
 * @phpstan-type CustomerUpdateArray array{address: string, name: string, shipping: string}
 * @phpstan-type CustomerParams array{customer: string, customer_update?: CustomerUpdateArray}
 *     |array{customer_email: string, customer_creation?: string}
 *     |array{customer_creation: string}
 */
final readonly class SinglePaymentCheckoutCustomer
{
    /** @var CustomerParams */
    private array $m_stripeParams;


    /**
     * This is private, so use createUsingCustomerId(), createUsingCustomer(), createUsingEmail() or create().
     * @param CustomerParams $stripeParams - the checkout session's parameters for the customer.
     */
    private function __construct(array $stripeParams)
    {
        $this->m_stripeParams = $stripeParams;
    }


    /**
     * The session is for an existing customer, by its ID.
     *
     * @param string $customerId - the customer's ID, e.g. "cus_123".
     *
     * @param CustomerUpdateConfig|null $customerUpdateConfig - optionally, which of the customer's details the session
     * may update with what the customer enters.
     *
     * @return SinglePaymentCheckoutCustomer
     *
     * @throws ExceptionInvalidValue - if $customerId isn't a customer's ID.
     */
    public static function createUsingCustomerId(
        string $customerId,
        ?CustomerUpdateConfig $customerUpdateConfig = null
    ) : SinglePaymentCheckoutCustomer
    {
        StripeIds::checkCustomerId($customerId);
        $params = ['customer' => $customerId];

        if ($customerUpdateConfig !== null)
        {
            $params['customer_update'] = $customerUpdateConfig->toArray();
        }

        return new SinglePaymentCheckoutCustomer($params);
    }


    /**
     * The session is for an existing customer, such as one just retrieved or created. Only its ID is used, so it must
     * be in the same Stripe account as the client the session is created with.
     *
     * @param Customer $customer - the customer.
     *
     * @param CustomerUpdateConfig|null $customerUpdateConfig - optionally, which of the customer's details the session
     * may update with what the customer enters.
     *
     * @return SinglePaymentCheckoutCustomer
     */
    public static function createUsingCustomer(
        Customer $customer,
        ?CustomerUpdateConfig $customerUpdateConfig = null
    ) : SinglePaymentCheckoutCustomer
    {
        return self::createUsingCustomerId($customer->getId(), $customerUpdateConfig);
    }


    /**
     * The session is for someone who isn't a customer yet, whose email address is filled in for them.
     *
     * @param string $email - the email address.
     *
     * @param CustomerCreation|null $customerCreation - optionally, whether Stripe creates a customer for them. Without
     * one, Stripe's default is used, which is CustomerCreation::IF_REQUIRED.
     *
     * @return SinglePaymentCheckoutCustomer
     *
     * @throws ExceptionInvalidValue - if $email isn't an email address.
     */
    public static function createUsingEmail(
        string $email,
        ?CustomerCreation $customerCreation = null
    ) : SinglePaymentCheckoutCustomer
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
        {
            throw new ExceptionInvalidValue("\"{$email}\" isn't an email address.");
        }

        $params = ['customer_email' => $email];

        if ($customerCreation !== null)
        {
            $params['customer_creation'] = $customerCreation->value;
        }

        return new SinglePaymentCheckoutCustomer($params);
    }


    /**
     * The session is for someone who isn't a customer yet, and whose email address isn't known, with whether Stripe
     * creates a customer for them. Leave the customer out of the session altogether for Stripe's default.
     * @param CustomerCreation $customerCreation - whether Stripe creates a customer for them.
     * @return SinglePaymentCheckoutCustomer
     */
    public static function create(CustomerCreation $customerCreation) : SinglePaymentCheckoutCustomer
    {
        return new SinglePaymentCheckoutCustomer(['customer_creation' => $customerCreation->value]);
    }


    /**
     * Get the parameters to add to a checkout session for this customer.
     * @return CustomerParams
     */
    public function getStripeParams() : array
    {
        return $this->m_stripeParams;
    }
}
