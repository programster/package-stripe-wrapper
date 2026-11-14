<?php

/*
 * A request that the FakeStripeHttpClient caught, instead of it being sent to Stripe.
 */

namespace Programster\Stripe\Testing;


final readonly class RecordedRequest
{
    private string $m_method;
    private string $m_path;

    /** @var list<string> */
    private array $m_headers;

    /** @var array<string, mixed> */
    private array $m_params;


    /**
     * @param string $method - "get", "post" or "delete".
     * @param string $path - the path that was requested, e.g. "/v1/customers".
     * @param list<string> $headers - the headers, as full "Name: value" strings.
     * @param array<string, mixed> $params - the parameters, as the SDK encoded them (e.g. booleans become "true").
     */
    public function __construct(string $method, string $path, array $headers, array $params)
    {
        $this->m_method = $method;
        $this->m_path = $path;
        $this->m_headers = $headers;
        $this->m_params = $params;
    }


    /**
     * Check whether the request had a header, by its name, ignoring case.
     * @param string $name - the header's name, e.g. "Idempotency-Key".
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        $found = false;

        foreach ($this->m_headers as $header)
        {
            if (stripos($header, "{$name}:") === 0)
            {
                $found = true;
            }
        }

        return $found;
    }


    /**
     * Get the value of one of the request's headers, by its name, ignoring case.
     * @param string $name - the header's name, e.g. "Idempotency-Key".
     * @return string
     * @throws \Exception - if the request had no such header.
     */
    public function getHeader(string $name): string
    {
        $value = null;

        foreach ($this->m_headers as $header)
        {
            if ($value === null && stripos($header, "{$name}:") === 0)
            {
                $value = trim(substr($header, strlen($name) + 1));
            }
        }

        if ($value === null)
        {
            throw new \Exception("The request had no {$name} header.");
        }

        return $value;
    }


    /**
     * Get the API key the request was authorised with, from its Authorization header.
     * @return string
     * @throws \Exception - if the request had no Authorization header.
     */
    public function getApiKey(): string
    {
        $apiKey = null;

        foreach ($this->m_headers as $header)
        {
            if ($apiKey === null && str_starts_with($header, 'Authorization: Bearer '))
            {
                $apiKey = substr($header, strlen('Authorization: Bearer '));
            }
        }

        if ($apiKey === null)
        {
            throw new \Exception("The request had no Authorization header.");
        }

        return $apiKey;
    }


    # Accessors
    public function getMethod(): string { return $this->m_method; }
    public function getPath(): string { return $this->m_path; }
    public function getParams(): array { return $this->m_params; }
}
