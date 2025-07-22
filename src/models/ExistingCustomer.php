<?php

/*
 * A secondary model to control the fact that one can only specify the $customerUpdateConfig when one has provided
 * the customer ID.
 */

namespace Programster\Stripe\Models;


readonly class ExistingCustomer
{
    /**
     * Create an object for specifying an existing customer, and optionally whether that customer's details
     * can be updated.
     *
     * @param string $customerStripeId - the ID of the existing customer in Stripe
     *
     * @param CustomerUpdateConfig|null $customerUpdateConfig - Controls what fields on Customer can be updated by the
     * Checkout Session.
     */
    public function __construct(
        private string $customerStripeId,
        private ?CustomerUpdateConfig $customerUpdateConfig = null
    )
    {

    }

    public function getStripeParams()
    {
        $params = ['customer' => $this->customerStripeId];

        if ($this->customerUpdateConfig !== null)
        {
            $params['customer_update'] = $this->customerUpdateConfig->toArray();
        }

        return $params;
    }
}