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

    # The recorded response (in testing/fixtures/responses/) to answer with for each type of object.
    private const FIXTURES = [
        'charge' => 'charge',
        'checkout.session' => 'checkout-session-hosted',
        'customer' => 'customer',
        'invoice' => 'invoice',
        'payment_intent' => 'payment-intent',
        'payment_method' => 'payment-method',
        'refund' => 'refund',
        'subscription' => 'subscription-active',
        'subscription_item' => 'subscription-item',
    ];

    private ?RecordedRequest $m_lastRequest = null;

    /** @var list<array<string, mixed>> */
    private array $m_queuedBodies = [];


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
     * @throws \JsonException - if the response can't be encoded, which would be a bug in the test.
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

        $objectType = 'unknown';

        foreach (explode('/', $path) as $segment)
        {
            if (isset(self::OBJECT_TYPES[$segment]))
            {
                $objectType = self::OBJECT_TYPES[$segment];
            }
        }

        # A test can say exactly what the next responses should be, e.g. several pages of a list. Otherwise, a list
        # holds the recorded object of its type, and anything else is the recorded object of its type, apart from a
        # new embedded checkout session, which is answered with the recorded embedded one.
        $isNewEmbeddedSession = (
               $method === 'post'
            && $objectType === 'checkout.session'
            && ($params['ui_mode'] ?? null) === 'embedded'
        );

        if (count($this->m_queuedBodies) > 0)
        {
            $body = array_shift($this->m_queuedBodies);
        }
        elseif ($isNewEmbeddedSession)
        {
            $body = self::loadFixture('checkout-session-embedded');
        }
        elseif ($isList)
        {
            $data = isset(self::FIXTURES[$objectType]) ? [self::loadFixture(self::FIXTURES[$objectType])] : [];
            $body = ['object' => 'list', 'data' => $data, 'has_more' => false, 'url' => $path];
        }
        elseif (isset(self::FIXTURES[$objectType]))
        {
            $body = self::loadFixture(self::FIXTURES[$objectType]);
        }
        else
        {
            $body = ['id' => 'fake_123', 'object' => $objectType];
        }

        return [json_encode($body, JSON_THROW_ON_ERROR), 200, ['request-id' => 'req_fake']];
    }


    /**
     * Answer the next request with this body, rather than the recorded object for its type. Queued bodies are used in
     * the order they were queued.
     * @param array<string, mixed> $body - the response's body, e.g. from loadFixture(), or a page of a list.
     * @return void
     */
    public function queueResponse(array $body): void
    {
        $this->m_queuedBodies[] = $body;
    }


    /**
     * Load one of the responses recorded from Stripe's test mode, in testing/fixtures/responses/.
     * @param string $name - the fixture's name, without ".json", e.g. "customer-deleted".
     * @return array<string, mixed>
     * @throws \Exception - if there's no such fixture.
     */
    public static function loadFixture(string $name): array
    {
        $path = __DIR__ . "/fixtures/responses/{$name}.json";
        $json = is_file($path) ? file_get_contents($path) : false;

        if ($json === false)
        {
            throw new \Exception("There's no recorded response called {$name}, in testing/fixtures/responses/.");
        }

        $body = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $fields = [];

        if (!is_array($body))
        {
            throw new \Exception("The recorded response {$name} isn't an object.");
        }

        foreach ($body as $key => $value)
        {
            if (!is_string($key))
            {
                throw new \Exception("The recorded response {$name} is a list, not an object.");
            }

            $fields[$key] = $value;
        }

        return $fields;
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
