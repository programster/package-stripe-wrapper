<?php

/*
 * Records real responses from Stripe's test mode into testing/fixtures/responses/, so the offline tests can build the
 * response models from what Stripe actually sends. Re-run it when Stripe's API version changes. It needs a test-mode
 * key in testing/settings.php, refuses to run with a live key, and deletes everything it creates (apart from the
 * products that inline product data creates, which Stripe can't delete).
 * Run with: php testing/record-fixtures.php
 */

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/settings.php');

if (!str_starts_with(STRIPE_SECRET_KEY, 'sk_test_') && !str_starts_with(STRIPE_SECRET_KEY, 'rk_test_'))
{
    echo "STRIPE_SECRET_KEY in testing/settings.php must be a test-mode key. Refusing to run.\n";
    exit(1);
}

$sdk = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
$directory = __DIR__ . '/fixtures/responses';
$metadata = ['created_by' => 'programster-stripe-wrapper-live-tests'];

# Save the raw body of the last response an object came from, with any secrets replaced, so it can't leak, even
# though it's only test mode.
$save = function (string $name, \Stripe\StripeObject $object) use ($directory): void {
    $body = $object->getLastResponse()->body;
    # Decoded to objects rather than arrays, so an empty object, such as "metadata": {}, stays an object.
    $decoded = json_decode($body, false, 512, JSON_THROW_ON_ERROR);

    $redact = function (mixed $value) use (&$redact): mixed {
        $redacted = $value;

        if (is_array($value))
        {
            $redacted = array_map($redact, $value);
        }
        elseif ($value instanceof \stdClass)
        {
            $redacted = new \stdClass();

            foreach (get_object_vars($value) as $key => $child)
            {
                $isSecret = ($key === 'client_secret' && is_string($child));
                $redacted->$key = $isSecret ? 'redacted_client_secret' : $redact($child);
            }
        }

        return $redacted;
    };

    $json = json_encode($redact($decoded), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    file_put_contents("{$directory}/{$name}.json", $json . "\n");
    echo "saved {$name}.json\n";
};

$testClock = $sdk->testHelpers->testClocks->create(['frozen_time' => time(), 'name' => 'fixture recording']);
$product = $sdk->products->create(['name' => 'Fixture product', 'metadata' => $metadata]);
$customerIds = [];

try
{
    $customer = $sdk->customers->create([
        'email' => 'jo@example.com',
        'name' => 'Jo Bloggs',
        'phone' => '+447700900000',
        'description' => 'Fixture customer',
        'address' => ['line1' => '1 High Street', 'city' => 'London', 'postal_code' => 'N1 1AA', 'country' => 'GB'],
        'shipping' => ['name' => 'Jo Bloggs', 'address' => ['line1' => '1 High Street', 'country' => 'GB']],
        'metadata' => $metadata,
        'preferred_locales' => ['en'],
    ]);

    $customerIds[] = $customer->id;
    $save('customer', $customer);
    $bare = $sdk->customers->create(['metadata' => $metadata]);
    $save('customer-with-nothing-set', $bare);
    $sdk->customers->delete($bare->id);
    $save('customer-deleted', $sdk->customers->retrieve($bare->id));

    $lineItem = [
        'quantity' => 1,
        'price_data' => ['currency' => 'gbp', 'unit_amount' => 2000, 'product' => $product->id],
    ];

    $hosted = $sdk->checkout->sessions->create([
        'mode' => 'payment',
        'line_items' => [$lineItem],
        'success_url' => 'https://example.com/success',
        'cancel_url' => 'https://example.com/cancel',
        'customer' => $customer->id,
        'client_reference_id' => 'order-42',
        'metadata' => $metadata,
    ]);

    $save('checkout-session-hosted', $hosted);
    $save('checkout-session-retrieved', $sdk->checkout->sessions->retrieve($hosted->id));
    $sdk->checkout->sessions->expire($hosted->id);

    $recurringItem = [
        'quantity' => 1,
        'price_data' => [
            'currency' => 'gbp',
            'unit_amount' => 999,
            'product' => $product->id,
            'recurring' => ['interval' => 'month'],
        ],
    ];

    $embedded = $sdk->checkout->sessions->create([
        'mode' => 'subscription',
        'ui_mode' => 'embedded',
        'line_items' => [$recurringItem],
        'return_url' => 'https://example.com/return',
        'customer_email' => 'sam@example.com',
    ]);

    $save('checkout-session-embedded', $embedded);
    $sdk->checkout->sessions->expire($embedded->id);

    # A customer on the test clock, with a card, for the subscription and the payments it makes.
    $subscriber = $sdk->customers->create([
        'email' => 'subscriber@example.com',
        'payment_method' => 'pm_card_visa',
        'invoice_settings' => ['default_payment_method' => 'pm_card_visa'],
        'test_clock' => $testClock->id,
        'metadata' => $metadata,
    ]);

    $paymentMethods = $sdk->customers->allPaymentMethods($subscriber->id, ['type' => 'card']);
    # An object from a list has no response of its own, so retrieve it.
    $save('payment-method', $sdk->paymentMethods->retrieve($paymentMethods->data[0]->id));
    $subscriptionItemPrice = [
        'currency' => 'gbp',
        'unit_amount' => 999,
        'product' => $product->id,
        'recurring' => ['interval' => 'month'],
    ];


    $subscription = $sdk->subscriptions->create([
        'customer' => $subscriber->id,
        'items' => [['price_data' => $subscriptionItemPrice, 'quantity' => 2]],
        'default_payment_method' => $paymentMethods->data[0]->id,
        'metadata' => $metadata,
        'expand' => ['latest_invoice'],
    ]);

    $save('subscription-active', $sdk->subscriptions->retrieve($subscription->id));
    $save('subscription-item', $sdk->subscriptionItems->retrieve($subscription->items->data[0]->id));
    $invoiceId = $subscription->latest_invoice->id;
    $save('invoice', $sdk->invoices->retrieve($invoiceId));
    $save('invoice-with-payments', $sdk->invoices->retrieve($invoiceId, ['expand' => ['payments']]));
    $paymentIntents = $sdk->paymentIntents->all(['customer' => $subscriber->id]);
    $save('payment-intent', $sdk->paymentIntents->retrieve($paymentIntents->data[0]->id));
    $charges = $sdk->charges->all(['customer' => $subscriber->id]);
    $save('charge', $sdk->charges->retrieve($charges->data[0]->id));
    $refund = $sdk->refunds->create(['charge' => $charges->data[0]->id, 'amount' => 500, 'metadata' => $metadata]);
    $save('refund', $sdk->refunds->retrieve($refund->id));

    $trial = $sdk->subscriptions->create([
        'customer' => $subscriber->id,
        'items' => [['price_data' => $subscriptionItemPrice]],
        'trial_period_days' => 14,
        'cancel_at_period_end' => true,
        'metadata' => $metadata,
    ]);

    $save('subscription-trialing', $sdk->subscriptions->retrieve($trial->id));

    $cancelled = $sdk->subscriptions->cancel(
        $trial->id,
        ['cancellation_details' => ['comment' => 'Too pricey', 'feedback' => 'too_expensive']]
    );

    $save('subscription-canceled', $cancelled);
}
finally
{
    foreach ($customerIds as $customerId)
    {
        $sdk->customers->delete($customerId);
    }

    # Deleting the test clock deletes its customer, subscriptions, invoices and payments.
    $sdk->testHelpers->testClocks->delete($testClock->id);
    $sdk->products->update($product->id, ['active' => false]);
    echo "cleaned up\n";
}
