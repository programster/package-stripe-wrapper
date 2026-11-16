<?php

/*
 * Smoke tests for checking webhooks' signatures, and the events they hold. These are run by testing/main.php, which
 * provides $smoke. Each request is signed here the way Stripe signs them, so nothing is sent to Stripe.
 * https://docs.stripe.com/webhooks#verify-manually
 */

use Programster\Stripe\Enums\EventType;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Models\WebhookSigningSecret;
use Programster\Stripe\StripeClient;
use Programster\Stripe\Testing\FakeStripeHttpClient;
use Programster\Stripe\Testing\SmokeTest;
use Stripe\Exception\SignatureVerificationException;

/** @var \Programster\Stripe\Testing\SmokeTest $smoke */

$client = new StripeClient('sk_test_webhooks');
$secret = new WebhookSigningSecret('whsec_test_secret');

# Sign a payload as Stripe does: an HMAC-SHA256 of the timestamp and payload, with the endpoint's signing secret.
$sign = function (string $payload, string $signingSecret, ?int $timestamp = null): string {
    $time = $timestamp ?? time();
    $signature = hash_hmac('sha256', "{$time}.{$payload}", $signingSecret);
    return "t={$time},v1={$signature}";
};

$event = [
    'id' => 'evt_123',
    'object' => 'event',
    'api_version' => '2025-10-29.clover',
    'created' => 1700000000,
    'livemode' => false,
    'type' => 'checkout.session.completed',
    'data' => ['object' => FakeStripeHttpClient::loadFixture('checkout-session-hosted')],
];

$payload = json_encode($event, JSON_THROW_ON_ERROR);

$smoke->run("verifyWebhookEvent gives the event of a correctly signed request", function () use (
    $client,
    $secret,
    $sign,
    $payload
): void {
    $verified = $client->verifyWebhookEvent($payload, $sign($payload, 'whsec_test_secret'), $secret);
    SmokeTest::assertSame('evt_123', $verified->getId(), "ID");
    SmokeTest::assertSame('checkout.session.completed', $verified->getType(), "type");
    SmokeTest::assertSame(true, $verified->hasType(EventType::CHECKOUT_SESSION_COMPLETED), "has its type");
    SmokeTest::assertSame(false, $verified->hasType(EventType::CHECKOUT_SESSION_EXPIRED), "hasn't another type");
    SmokeTest::assertSame(1700000000, $verified->getCreated()->getTimestamp(), "created");
    SmokeTest::assertSame('2025-10-29.clover', $verified->getApiVersion(), "API version");
    SmokeTest::assertSame(false, $verified->isLivemode(), "livemode");
    SmokeTest::assertSame('checkout.session', $verified->getDataObjectType(), "data object's type");
    SmokeTest::assertSame('checkout.session', $verified->getDataObject()->toArray()['object'], "SDK data object");
    SmokeTest::assertSame('evt_123', $verified->getStripeObject()->id, "SDK event");
    $session = $verified->getCheckoutSession();
    $expectedSessionId = FakeStripeHttpClient::loadFixture('checkout-session-hosted')['id'];
    SmokeTest::assertSame($expectedSessionId, $session->getId(), "the session");

    $getWrongType = function () use ($verified): void {
        $verified->getSubscription();
    };

    SmokeTest::expectException(ExceptionUnexpectedResponse::class, $getWrongType, "not a checkout.session");
});

$smoke->run("verifyWebhookEvent gives each kind of object as its model", function () use (
    $client,
    $secret,
    $sign
): void {
    $fixtures = [
        'getCustomer' => 'customer',
        'getSubscription' => 'subscription-active',
        'getInvoice' => 'invoice',
        'getPaymentIntent' => 'payment-intent',
        'getCharge' => 'charge',
        'getRefund' => 'refund',
        'getPaymentMethod' => 'payment-method',
    ];

    foreach ($fixtures as $getter => $fixture)
    {
        $object = FakeStripeHttpClient::loadFixture($fixture);
        $body = ['id' => 'evt_1', 'object' => 'event', 'created' => 1, 'livemode' => false, 'type' => 'x.y'];
        $body['data'] = ['object' => $object];
        $payload = json_encode($body, JSON_THROW_ON_ERROR);
        $verified = $client->verifyWebhookEvent($payload, $sign($payload, 'whsec_test_secret'), $secret);
        SmokeTest::assertSame($object['id'], $verified->$getter()->getId(), $getter);
    }
});

$smoke->run("verifyWebhookEvent rejects a request Stripe didn't sign", function () use (
    $client,
    $secret,
    $sign,
    $payload
): void {
    $signedWithAnotherSecret = function () use ($client, $secret, $sign, $payload): void {
        $client->verifyWebhookEvent($payload, $sign($payload, 'whsec_another_endpoint'), $secret);
    };

    SmokeTest::expectException(SignatureVerificationException::class, $signedWithAnotherSecret, "matching");

    $changedPayload = function () use ($client, $secret, $sign, $payload): void {
        $signature = $sign($payload, 'whsec_test_secret');
        $client->verifyWebhookEvent(str_replace('evt_123', 'evt_456', $payload), $signature, $secret);
    };

    SmokeTest::expectException(SignatureVerificationException::class, $changedPayload, "matching");

    # Decoding and encoding the body again changes it, e.g. by escaping slashes, so it no longer matches.
    $reencodedPayload = function () use ($client, $secret, $sign, $payload): void {
        $signature = $sign($payload, 'whsec_test_secret');
        $reencoded = json_encode(json_decode($payload, true), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $client->verifyWebhookEvent($reencoded, $signature, $secret);
    };

    SmokeTest::expectException(SignatureVerificationException::class, $reencodedPayload, "matching");

    $noSignature = function () use ($client, $secret, $payload): void {
        $client->verifyWebhookEvent($payload, '', $secret);
    };

    SmokeTest::expectException(SignatureVerificationException::class, $noSignature);
});

$smoke->run("verifyWebhookEvent rejects a replayed request", function () use ($client, $secret, $sign, $payload): void {
    $tenMinutesAgo = time() - 600;

    $replayed = function () use ($client, $secret, $sign, $payload, $tenMinutesAgo): void {
        $client->verifyWebhookEvent($payload, $sign($payload, 'whsec_test_secret', $tenMinutesAgo), $secret);
    };

    SmokeTest::expectException(SignatureVerificationException::class, $replayed, "tolerance");

    $signature = $sign($payload, 'whsec_test_secret', $tenMinutesAgo);
    $verified = $client->verifyWebhookEvent($payload, $signature, $secret, toleranceSeconds: 900);
    SmokeTest::assertSame('evt_123', $verified->getId(), "a longer tolerance");

    $noTolerance = function () use ($client, $secret, $signature, $payload): void {
        $client->verifyWebhookEvent($payload, $signature, $secret, toleranceSeconds: 0);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $noTolerance, "at least 1 second");
});

$smoke->run("verifyWebhookEvent rejects a signed payload that isn't an event", function () use (
    $client,
    $secret,
    $sign
): void {
    $notJson = function () use ($client, $secret, $sign): void {
        $client->verifyWebhookEvent('not json', $sign('not json', 'whsec_test_secret'), $secret);
    };

    SmokeTest::expectException(ExceptionUnexpectedResponse::class, $notJson, "isn't JSON");

    $customer = json_encode(FakeStripeHttpClient::loadFixture('customer'), JSON_THROW_ON_ERROR);

    $notAnEvent = function () use ($client, $secret, $sign, $customer): void {
        $client->verifyWebhookEvent($customer, $sign($customer, 'whsec_test_secret'), $secret);
    };

    SmokeTest::expectException(ExceptionUnexpectedResponse::class, $notAnEvent, "not a customer");
});

$smoke->run("WebhookSigningSecret rejects what isn't a signing secret, and hides it", function (): void {
    $apiKey = function (): void {
        new WebhookSigningSecret('sk_test_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $apiKey, "isn't the API's secret key");

    $justThePrefix = function (): void {
        new WebhookSigningSecret('whsec_');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $justThePrefix, "whsec_");

    $secret = new WebhookSigningSecret('whsec_do_not_show');
    $output = print_r($secret, true);
    assert(!str_contains($output, 'whsec_do_not_show'), "print_r showed the secret: {$output}");

    $serialize = function () use ($secret): void {
        serialize($secret);
    };

    SmokeTest::expectException(\LogicException::class, $serialize, "can't be serialized");
});
