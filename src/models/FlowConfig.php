<?php

/*
 * A custom type for inputting all of the flow configuration. This helps workaround the fact that when one uses
 * an embedded flow, certain parameters are required, and when using hosted flow, other parameters can be used or
 * required.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\RedirectOnCompletion;
use Programster\Stripe\Enums\UiMode;

final readonly class FlowConfig
{
    private readonly UiMode $m_mode;
    private readonly ?string $m_successUrl;
    private readonly ?string $m_returnUrl;
    private readonly ?string $m_cancelUrl;
    private readonly ?RedirectOnCompletion $m_redirectOnCompletion;


    /**
     * This is private, so use createHosted() or createEmbedded(), which only take what applies to that flow.
     *
     * @param UiMode $mode - hosted or embedded.
     *
     * @param string|null $successUrl - where Stripe sends the customer when they're done (hosted only).
     *
     * @param string|null $returnUrl - where Stripe sends the customer back to.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-return_url
     *
     * @param string|null $cancelUrl - where Stripe sends the customer if they cancel (hosted only).
     *
     * @param RedirectOnCompletion|null $redirectOnCompletion - when to redirect to the return URL (embedded only).
     */
    private function __construct(
        UiMode $mode,
        ?string $successUrl,
        ?string $returnUrl,
        ?string $cancelUrl,
        ?RedirectOnCompletion $redirectOnCompletion
    )
    {
        $this->m_mode = $mode;
        $this->m_successUrl = $successUrl;
        $this->m_returnUrl = $returnUrl;
        $this->m_cancelUrl = $cancelUrl;
        $this->m_redirectOnCompletion = $redirectOnCompletion;
    }


    /**
     * Create a hosted flow, in which the customer's browser is sent to a payment page on Stripe's website.
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
     * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-cancel_url
     *
     * @return FlowConfig
     */
    public static function createHosted(
        string $successUrl,
        ?string $returnUrl = null,
        ?string $cancelUrl = null
    ) : FlowConfig
    {
        return new FlowConfig(UiMode::HOSTED, $successUrl, $returnUrl, $cancelUrl, null);
    }


    /**
     * Create an embedded flow, in which the user's browser is not taken to the stripe website, but the Stripe payment
     * form is embedded in your website. A success URL and cancel URL are explicitly not allowed in embedded mode.
     *
     * @param string $returnUrl - The URL to redirect your customer back to after they complete the payment.
     *
     * @param RedirectOnCompletion $redirectOnCompletion - when to redirect to the return URL. Defaults to always.
     *
     * @return FlowConfig
     */
    public static function createEmbedded(
        string $returnUrl,
        RedirectOnCompletion $redirectOnCompletion = RedirectOnCompletion::ALWAYS
    ) : FlowConfig
    {
        return new FlowConfig(UiMode::EMBEDDED, null, $returnUrl, null, $redirectOnCompletion);
    }


    /**
     * Get the parameters to add to a checkout session for this flow.
     * @return array<string, string>
     */
    public function getStripeParams() : array
    {
        $params = [
            'ui_mode' => $this->m_mode->value
        ];

        if ($this->m_successUrl !== null)
        {
            $params['success_url'] = $this->m_successUrl;
        }

        if ($this->m_returnUrl !== null)
        {
            $params['return_url'] = $this->m_returnUrl;
        }

        if ($this->m_cancelUrl !== null)
        {
            $params['cancel_url'] = $this->m_cancelUrl;
        }

        if ($this->m_redirectOnCompletion !== null)
        {
            $params['redirect_on_completion'] = $this->m_redirectOnCompletion->value;
        }

        return $params;
    }
}
