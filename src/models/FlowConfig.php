<?php

/*
 * A custom type for inputting all of the flow configuration. This helps workaround the fact that when one uses
 * an embedded flow, certain parameters are required, and when using hosted flow, other parameters can be used or
 * required.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\RedirectOnCompletion;
use Programster\Stripe\enums\UiMode;

class FlowConfig
{
    private ?string $successUrl = null;
    private ?string $returnUrl = null; // https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-return_url

    private ?RedirectOnCompletion $redirectOnCompletion = null;

    private function __construct(private readonly UiMode $mode)
    {

    }


    /**
     * Create a flow configuration, for whether the user should be directed to a hosted site, or whether Stripe will
     * be embedded in your site.
     *
     * @param string $successUrl - The URL to which Stripe should send customers when payment or setup is complete.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-success_url
     *
     * @param $returnUrl - The URL to redirect your customer back to after they authenticate or cancel their payment on
     * the payment method’s app or site.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-return_url
     *
     * @param ?string $cancelUrl - If set, Checkout displays a back button and customers will be directed to this URL
     * if they decide to cancel payment and return to your website.
     * https://docs.stripe.com/api/checkout/sessions/object#checkout_session_object-cancel_url
     * @return void
     */
    public static function createHosted(string $successUrl, ?string $returnUrl = null, ?string $cancelUrl = null) : FlowConfig
    {
        $flowConfig = new FlowConfig(UiMode::HOSTED);
        $flowConfig->successUrl = $successUrl;
        $flowConfig->returnUrl = $returnUrl;
        return $flowConfig;
    }


    /**
     * Create an embedded flow, in which the user's browser is not taken to the stripe website, but the Stripe payment
     * is embedded form in your website.
     * success url is explicitly not allowed in embedded mode.
     * cancel url is explicitly not allowed in embedded mode.
     * @param string $returnUrl
     * @return FlowConfig
     */
    public static function createEmbedded(
        string $returnUrl,
        RedirectOnCompletion $redirectOnCompletion = RedirectOnCompletion::ALWAYS
    ) : FlowConfig
    {
        $flowConfig = new FlowConfig(UiMode::EMBEDDED);
        $flowConfig->returnUrl = $returnUrl;
        $flowConfig->redirectOnCompletion = $redirectOnCompletion;
        return $flowConfig;
    }


    public function getStripeParams() : array
    {
        $params = [
            'ui_mode' => $this->mode->value
        ];

        if ($this->successUrl !== null)
        {
            $params['success_url'] = $this->successUrl;
        }

        if ($this->returnUrl !== null)
        {
            $params['return_url'] = $this->returnUrl;
        }

        if ($this->redirectOnCompletion !== null)
        {
            $params['redirect_on_completion'] = $this->redirectOnCompletion->value;
        }

        return $params;
    }
}