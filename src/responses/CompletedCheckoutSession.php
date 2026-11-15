<?php

/*
 * A checkout session the customer has completed, so it's certain to have the details they gave, such as their email
 * address and billing address (where those were collected). Get one from CheckoutSession::toCompleted(), e.g. when
 * handling a checkout.session.completed webhook. Use getSession() for the session's other details.
 * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-customer_details
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Exceptions\ExceptionWrongStatus;


final readonly class CompletedCheckoutSession
{
    private CheckoutSession $m_session;
    private CustomerDetails $m_customerDetails;


    /**
     * @param CheckoutSession $session - the completed session.
     * @throws ExceptionWrongStatus - if the session hasn't been completed. Check isComplete() first.
     * @throws ExceptionUnexpectedResponse - if the completed session has no customer details.
     */
    public function __construct(CheckoutSession $session)
    {
        if (!$session->isComplete())
        {
            $status = $session->getStatus();
            $currently = ($status !== null) ? "is {$status}" : "has no status";

            throw new ExceptionWrongStatus(
                "Checkout session {$session->getId()} {$currently}, so it hasn't been completed. Check isComplete() " .
                "first."
            );
        }

        $customerDetails = $session->getCustomerDetails();

        if ($customerDetails === null)
        {
            throw new ExceptionUnexpectedResponse(
                "Checkout session {$session->getId()} has been completed, but has no customer details."
            );
        }

        $this->m_session = $session;
        $this->m_customerDetails = $customerDetails;
    }


    /**
     * Get the session's ID.
     * @return string
     */
    public function getId(): string
    {
        return $this->m_session->getId();
    }


    # Accessors
    public function getSession(): CheckoutSession { return $this->m_session; }
    public function getCustomerDetails(): CustomerDetails { return $this->m_customerDetails; }
}
