<?php

/*
 * An embedded checkout session that's just been created, so it's certain to have the client secret that Stripe.js
 * needs to show the payment form in your page. Use getSession() for the session's other details.
 * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-client_secret
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;


final readonly class NewEmbeddedCheckoutSession
{
    private CheckoutSession $m_session;
    private string $m_clientSecret;


    /**
     * @param CheckoutSession $session - the session Stripe has just created.
     * @throws ExceptionUnexpectedResponse - if the session has no client secret, e.g. because it isn't an embedded one.
     * @internal - these are created by the checkout methods for embedded sessions.
     */
    public function __construct(CheckoutSession $session)
    {
        $clientSecret = $session->getClientSecret();

        if ($clientSecret === null)
        {
            throw new ExceptionUnexpectedResponse("Checkout session {$session->getId()} has no client secret.");
        }

        $this->m_session = $session;
        $this->m_clientSecret = $clientSecret;
    }


    /**
     * Get the session's ID, e.g. to store, and retrieve the session with later.
     * @return string
     */
    public function getId(): string
    {
        return $this->m_session->getId();
    }


    # Accessors
    public function getSession(): CheckoutSession { return $this->m_session; }
    public function getClientSecret(): string { return $this->m_clientSecret; }
}
