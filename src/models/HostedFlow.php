<?php

/*
 * A hosted checkout flow, in which the customer's browser is sent to a payment page on Stripe's website, and the URLs
 * Stripe sends them back to. For a payment form embedded in your own site, use EmbeddedFlow, with the checkout
 * methods for it.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-ui_mode
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\UiMode;


final readonly class HostedFlow
{
    private string $m_successUrl;
    private ?string $m_returnUrl;
    private ?string $m_cancelUrl;


    /**
     * Create a hosted flow.
     *
     * @param string $successUrl - The URL to which Stripe should send customers when payment or setup is complete.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-success_url
     *
     * @param string|null $returnUrl - The URL to redirect your customer back to after they authenticate or cancel
     * their payment on the payment method’s app or site.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-return_url
     *
     * @param string|null $cancelUrl - If set, Checkout displays a back button and customers will be directed to this
     * URL if they decide to cancel payment and return to your website.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-cancel_url
     */
    public function __construct(string $successUrl, ?string $returnUrl = null, ?string $cancelUrl = null)
    {
        $this->m_successUrl = $successUrl;
        $this->m_returnUrl = $returnUrl;
        $this->m_cancelUrl = $cancelUrl;
    }


    /**
     * Get the parameters to add to a checkout session for this flow.
     * @return array{ui_mode: string, success_url: string, return_url?: string, cancel_url?: string}
     */
    public function getStripeParams() : array
    {
        $params = [
            'ui_mode' => UiMode::HOSTED->value,
            'success_url' => $this->m_successUrl,
        ];

        if ($this->m_returnUrl !== null)
        {
            $params['return_url'] = $this->m_returnUrl;
        }

        if ($this->m_cancelUrl !== null)
        {
            $params['cancel_url'] = $this->m_cancelUrl;
        }

        return $params;
    }
}
