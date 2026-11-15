<?php

/*
 * An embedded checkout flow, in which Stripe's payment form is embedded in your own site, rather than the customer
 * being sent to Stripe's. A success URL and cancel URL aren't allowed in this flow, so it doesn't take them. For a
 * payment page hosted by Stripe, use HostedFlow, with the checkout methods for it.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-ui_mode
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\RedirectOnCompletion;
use Programster\Stripe\Enums\UiMode;


final readonly class EmbeddedFlow
{
    private string $m_returnUrl;
    private RedirectOnCompletion $m_redirectOnCompletion;


    /**
     * Create an embedded flow.
     *
     * @param string $returnUrl - The URL to redirect your customer back to after they complete the payment.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-return_url
     *
     * @param RedirectOnCompletion $redirectOnCompletion - when to redirect to the return URL. Defaults to always.
     */
    public function __construct(
        string $returnUrl,
        RedirectOnCompletion $redirectOnCompletion = RedirectOnCompletion::ALWAYS
    )
    {
        $this->m_returnUrl = $returnUrl;
        $this->m_redirectOnCompletion = $redirectOnCompletion;
    }


    /**
     * Get the parameters to add to a checkout session for this flow.
     * @return array{ui_mode: string, return_url: string, redirect_on_completion: string}
     */
    public function getStripeParams() : array
    {
        return [
            'ui_mode' => UiMode::EMBEDDED->value,
            'return_url' => $this->m_returnUrl,
            'redirect_on_completion' => $this->m_redirectOnCompletion->value,
        ];
    }
}
