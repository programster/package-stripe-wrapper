<?php

/*
 * A webhook endpoint's signing secret, which StripeClient::verifyWebhookEvent() checks each event's signature with.
 * It's in the dashboard's settings for the endpoint (or printed by "stripe listen", for the Stripe CLI), and starts
 * "whsec_". It isn't the API's secret key, which is the usual mix-up. Like the client, it stays out of var_dump() and
 * print_r() output, and can't be serialized.
 * https://docs.stripe.com/webhooks#verify-events
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionInvalidValue;


final readonly class WebhookSigningSecret
{
    private string $m_secret;


    /**
     * @param string $secret - the endpoint's signing secret, e.g. "whsec_...".
     * @throws ExceptionInvalidValue - if it doesn't start "whsec_", e.g. because the API's secret key was given.
     */
    public function __construct(#[\SensitiveParameter] string $secret)
    {
        if (!str_starts_with($secret, 'whsec_') || strlen($secret) === strlen('whsec_'))
        {
            # The secret itself is left out of the message, as it may be the API's secret key.
            throw new ExceptionInvalidValue(
                "A webhook signing secret starts \"whsec_\", and is in the dashboard's settings for the webhook " .
                "endpoint. It isn't the API's secret key."
            );
        }

        $this->m_secret = $secret;
    }


    /**
     * Keep the secret out of var_dump() and print_r() output, e.g. in logs or error pages.
     * @return array<string, string>
     */
    public function __debugInfo() : array
    {
        return ['secret' => '[redacted]'];
    }


    /**
     * The secret can't be serialized, so it can't end up in a cache or session by accident.
     * @return array<string, mixed>
     * @throws \LogicException - always.
     */
    public function __serialize() : array
    {
        throw new \LogicException("A WebhookSigningSecret can't be serialized.");
    }


    /**
     * Get the secret itself, for checking a signature with.
     * @return string
     * @internal - for StripeClient::verifyWebhookEvent().
     */
    public function getSecret() : string
    {
        return $this->m_secret;
    }
}
