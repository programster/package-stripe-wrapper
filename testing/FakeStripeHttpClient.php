<?php

/*
 * An HTTP client for stripe/stripe-php that never contacts Stripe. It records each request, so that tests can check
 * exactly what this package would have sent, and answers with a minimal, made-up response, so that the SDK can turn
 * it into the object (or list of objects) the method returns.
 */

namespace Programster\Stripe\Testing;

use Stripe\HttpClient\ClientInterface;


final class FakeStripeHttpClient implements ClientInterface
{
    # The type of object Stripe returns for each kind of thing in a path, which the SDK uses to decide which class to
    # turn the response into (e.g. "subscription" becomes a \Stripe\Subscription).
    private const OBJECT_TYPES = [
        'charges' => 'charge',
        'customers' => 'customer',
        'invoices' => 'invoice',
        'payment_intents' => 'payment_intent',
        'payment_methods' => 'payment_method',
        'refunds' => 'refund',
        'sessions' => 'checkout.session',
        'subscription_items' => 'subscription_item',
        'subscriptions' => 'subscription',
    ];

    private ?RecordedRequest $m_lastRequest = null;


    public function __construct()
    {
    }


    /**
     * Record the request, and answer it with a made-up response, without contacting Stripe.
     *
     * @param string $method - "get", "post" or "delete".
     *
     * @param string $absUrl - the URL being requested.
     *
     * @param list<string> $headers - the headers, as full "Name: value" strings.
     *
     * @param array<string, mixed> $params - the parameters, as the SDK encoded them (e.g. booleans become "true" and
     * "false").
     *
     * @param bool $hasFile - whether a file is being uploaded.
     *
     * @param string $apiMode - "v1" or "v2".
     *
     * @param int|null $maxNetworkRetries - how many times to retry.
     *
     * @return array{0: string, 1: int, 2: array<string, string>} - the response body, HTTP status code and headers.
     *
     * @throws \Exception - if the URL has no path, which the SDK never sends.
     */
    public function request(
        $method,
        $absUrl,
        $headers,
        $params,
        $hasFile,
        $apiMode = 'v1',
        $maxNetworkRetries = null
    ): array
    {
        $path = parse_url($absUrl, PHP_URL_PATH);

        if (!is_string($path))
        {
            throw new \Exception("The SDK sent a request to a URL with no path: {$absUrl}");
        }

        $this->m_lastRequest = new RecordedRequest($method, $path, $headers, $params);
        $lastSegment = basename($path);

        # A GET of a collection (e.g. /v1/subscriptions) is a list, while anything else (e.g. a GET of
        # /v1/checkout/sessions/cs_123, or a POST) is a single object, of the last type of thing named in the path.
        $isList = ($method === 'get' && isset(self::OBJECT_TYPES[$lastSegment]));

        if ($isList)
        {
            $body = ['object' => 'list', 'data' => [], 'has_more' => false, 'url' => $path];
        }
        else
        {
            $objectType = 'unknown';

            foreach (explode('/', $path) as $segment)
            {
                if (isset(self::OBJECT_TYPES[$segment]))
                {
                    $objectType = self::OBJECT_TYPES[$segment];
                }
            }

            $body = ['id' => 'fake_123', 'object' => $objectType, 'url' => 'https://checkout.stripe.com/fake'];
        }

        return [json_encode($body), 200, ['request-id' => 'req_fake']];
    }


    /**
     * Get the last request that was made, throwing if none has been, so a test can't check a stale request.
     * Calling this forgets it, so the next check needs a new request.
     * @return RecordedRequest
     * @throws \Exception - if no request has been made since the last call.
     */
    public function takeLastRequest(): RecordedRequest
    {
        if ($this->m_lastRequest === null)
        {
            throw new \Exception("No request was sent to Stripe.");
        }

        $request = $this->m_lastRequest;
        $this->m_lastRequest = null;
        return $request;
    }
}
