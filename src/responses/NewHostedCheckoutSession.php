<?php

/*
 * A hosted checkout session that's just been created, so it's certain to have the URL of its payment page, to send
 * the customer to. Stripe empties the URL once a session is completed or expires, so a session retrieved later is a
 * plain CheckoutSession, whose getUrl() can return null. Use getSession() for the session's other details.
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;


final readonly class NewHostedCheckoutSession
{
    private CheckoutSession $m_session;
    private string $m_url;


    /**
     * @param CheckoutSession $session - the session Stripe has just created.
     * @throws ExceptionUnexpectedResponse - if the session has no URL, e.g. because it isn't a hosted one.
     * @internal - these are created by the checkout methods for hosted sessions.
     */
    public function __construct(CheckoutSession $session)
    {
        $url = $session->getUrl();

        if ($url === null)
        {
            throw new ExceptionUnexpectedResponse(
                "Checkout session {$session->getId()} has no URL to send the customer to."
            );
        }

        $this->m_session = $session;
        $this->m_url = $url;
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
    public function getUrl(): string { return $this->m_url; }
}
