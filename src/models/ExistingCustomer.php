<?php

/*
 * A secondary model to control the fact that one can only specify the $customerUpdateConfig when one has provided
 * the customer ID.
 */

namespace Programster\Stripe\Models;

readonly class ExistingCustomer
{
    private string $m_customerStripeId;
    private ?CustomerUpdateConfig $m_customerUpdateConfig;


    /**
     * Create an object for specifying an existing customer, and optionally whether that customer's details
     * can be updated.
     *
     * @param string $customerStripeId - the ID of the existing customer in Stripe
     *
     * @param CustomerUpdateConfig|null $customerUpdateConfig - Controls what fields on Customer can be updated by the
     * Checkout Session.
     */
    public function __construct(string $customerStripeId, ?CustomerUpdateConfig $customerUpdateConfig = null)
    {
        $this->m_customerStripeId = $customerStripeId;
        $this->m_customerUpdateConfig = $customerUpdateConfig;
    }


    /**
     * Get the parameters to add to a checkout session for this customer.
     * @return array<string, mixed>
     */
    public function getStripeParams() : array
    {
        $params = ['customer' => $this->m_customerStripeId];

        if ($this->m_customerUpdateConfig !== null)
        {
            $params['customer_update'] = $this->m_customerUpdateConfig->toArray();
        }

        return $params;
    }


    # Accessors
    public function getCustomerId() : string { return $this->m_customerStripeId; }
}
