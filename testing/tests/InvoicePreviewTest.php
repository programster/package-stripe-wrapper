<?php

/*
 * Smoke tests for previewing invoices. These are run by testing/main.php, which provides $smoke and $fakeHttpClient.
 * Each preview is answered with one recorded from test mode: a subscription's quantity changed from 2 to 3, which
 * credits the unused time at 2, charges the rest of the period at 3, and then the next period at 3.
 */

use Programster\Stripe\Collections\SubscriptionItemChangeCollection;
use Programster\Stripe\Collections\SubscriptionLineItemCollection;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\ProrationBehavior;
use Programster\Stripe\Enums\RecurringInterval;
use Programster\Stripe\Exceptions\ExceptionDuplicateKey;
use Programster\Stripe\Exceptions\ExceptionIncompleteList;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Models\PriceDataForSubscriptionItem;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SubscriptionCustomer;
use Programster\Stripe\Models\SubscriptionItem;
use Programster\Stripe\Models\SubscriptionItemChange;
use Programster\Stripe\Models\SubscriptionItemPrice;
use Programster\Stripe\StripeClient;
use Programster\Stripe\Testing\FakeStripeHttpClient;
use Programster\Stripe\Testing\SmokeTest;

/** @var \Programster\Stripe\Testing\SmokeTest $smoke */
/** @var \Programster\Stripe\Testing\FakeStripeHttpClient $fakeHttpClient */

$client = new StripeClient('sk_test_previews');

$smoke->run("previewUpcomingInvoice", function () use ($client, $fakeHttpClient): void {
    $preview = $client->previewUpcomingInvoice('sub_123');
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('post', $request->getMethod(), "method");
    SmokeTest::assertSame('/v1/invoices/create_preview', $request->getPath(), "path");
    SmokeTest::assertSame(['subscription' => 'sub_123'], $request->getParams(), "params");

    $fixture = FakeStripeHttpClient::loadFixture('invoice-preview');
    $prorationDate = SmokeTest::getNested($fixture, 'parent', 'subscription_details', 'subscription_proration_date');
    SmokeTest::assertSame($fixture['id'], $preview->getId(), "ID");
    SmokeTest::assertSame($fixture['customer'], $preview->getCustomerId(), "customer");
    SmokeTest::assertSame(3996, $preview->getAmountDue()->getAmount(), "amount due");
    SmokeTest::assertSame(true, $preview->getTotal()->hasCurrency(Currency::GBP), "currency");
    SmokeTest::assertSame(3996, $preview->getSubtotal()->getAmount(), "subtotal");
    SmokeTest::assertSame($fixture['period_end'], $preview->getPeriodEnd()->getTimestamp(), "period end");
    SmokeTest::assertSame($prorationDate, $preview->getProrationDate()?->getTimestamp(), "proration date");
    SmokeTest::assertSame(true, $preview->hasAllLines(), "has all lines");

    $lines = $preview->getLines();
    SmokeTest::assertSame(3, count($lines), "number of lines");
    $credit = $lines->first();
    SmokeTest::assertSame(-1998, $credit->getAmount()->getAmount(), "credit's amount");
    SmokeTest::assertSame(true, $credit->isProration(), "credit is a proration");
    SmokeTest::assertSame(2, $credit->getQuantity(), "credit's quantity");
    SmokeTest::assertSame(true, str_starts_with((string)$credit->getDescription(), 'Unused time'), "description");
    SmokeTest::assertSame(true, str_starts_with((string)$credit->getPriceId(), 'price_'), "price ID");
    SmokeTest::assertSame(true, str_starts_with((string)$credit->getSubscriptionItemId(), 'si_'), "item ID");
    SmokeTest::assertSame(false, $lines->last()->isProration(), "next period isn't a proration");
    SmokeTest::assertSame('invoice', $preview->getStripeObject()->object, "SDK object");

    $wrongId = function () use ($client): void {
        $client->previewUpcomingInvoice('si_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $wrongId, "sub_");
});

$smoke->run("an invoice preview with more lines than Stripe included", function () use (
    $client,
    $fakeHttpClient
): void {
    $fixture = FakeStripeHttpClient::loadFixture('invoice-preview');
    $lines = $fixture['lines'];
    assert(is_array($lines));
    $lines['has_more'] = true;
    $fixture['lines'] = $lines;
    $fakeHttpClient->queueResponse($fixture);
    $preview = $client->previewUpcomingInvoice('sub_123');
    SmokeTest::assertSame(false, $preview->hasAllLines(), "has all lines");
    SmokeTest::assertSame(3996, $preview->getAmountDue()->getAmount(), "the total still includes every line");

    $getLines = function () use ($preview): void {
        $preview->getLines();
    };

    SmokeTest::expectException(ExceptionIncompleteList::class, $getLines, "more lines");
});

$smoke->run("Subscription::fetchUpcomingInvoicePreview", function () use ($client, $fakeHttpClient): void {
    $subscription = $client->retrieveSubscription('sub_123');
    $subscription->fetchUpcomingInvoicePreview();
    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame(['subscription' => $subscription->getId()], $params, "params");
});

$smoke->run("previewSubscriptionChange", function () use ($client, $fakeHttpClient): void {
    $monthly = new RecurringConfig(RecurringInterval::MONTH, 1);
    $newPrice = new PriceDataForSubscriptionItem(Currency::GBP, 500, 'prod_123', $monthly);

    $changes = new SubscriptionItemChangeCollection(
        SubscriptionItemChange::createUpdateUsingItemId('si_1', quantity: 3),
        SubscriptionItemChange::createUpdateUsingItemId(
            'si_2',
            SubscriptionItemPrice::createUsingExistingPrice('price_premium'),
            2
        ),
        SubscriptionItemChange::createRemovalUsingItemId('si_3'),
        SubscriptionItemChange::createAddition(SubscriptionItem::createUsingInlinePrice($newPrice, 1)),
    );

    $client->previewSubscriptionChange(
        'sub_123',
        $changes,
        ProrationBehavior::CREATE,
        new DateTimeImmutable('@1700000000'),
    );

    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/invoices/create_preview', $request->getPath(), "path");

    $expected = [
        'subscription' => 'sub_123',
        'subscription_details' => [
            'items' => [
                ['id' => 'si_1', 'quantity' => 3],
                ['id' => 'si_2', 'price' => 'price_premium', 'quantity' => 2],
                ['id' => 'si_3', 'deleted' => 'true'],
                [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'gbp',
                        'unit_amount' => 500,
                        'product' => 'prod_123',
                        'recurring' => ['interval' => 'month', 'interval_count' => 1],
                    ],
                ],
            ],
            'proration_behavior' => 'create_prorations',
            'proration_date' => 1700000000,
        ],
    ];

    SmokeTest::assertSame($expected, $request->getParams(), "params");

    $item = $client->retrieveSubscription('sub_123')->getItems()->first();
    $byItem = new SubscriptionItemChangeCollection(SubscriptionItemChange::createRemovalUsingItem($item));
    $client->previewSubscriptionChange('sub_123', $byItem);
    $params = $fakeHttpClient->takeLastRequest()->getParams();
    $removed = [['id' => $item->getId(), 'deleted' => 'true']];
    SmokeTest::assertSame($removed, SmokeTest::getNested($params, 'subscription_details', 'items'), "by item");

    $quantityOfItem = SubscriptionItemChange::createUpdateUsingItem($item, quantity: 4)->toArray();
    SmokeTest::assertSame(['id' => $item->getId(), 'quantity' => 4], $quantityOfItem, "update by item");
});

$smoke->run("previewSubscriptionChange rejects changes that can't be made", function () use ($client): void {
    $noChanges = function () use ($client): void {
        $client->previewSubscriptionChange('sub_123', new SubscriptionItemChangeCollection());
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $noChanges, "at least one change");

    $nothingChanged = function (): void {
        SubscriptionItemChange::createUpdateUsingItemId('si_1');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $nothingChanged, "a new price, a new quantity");

    $priceIdAsItemId = function (): void {
        SubscriptionItemChange::createRemovalUsingItemId('price_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $priceIdAsItemId, "si_");

    $sameItemTwice = function (): void {
        new SubscriptionItemChangeCollection(
            SubscriptionItemChange::createUpdateUsingItemId('si_1', quantity: 2),
            SubscriptionItemChange::createRemovalUsingItemId('si_1'),
        );
    };

    SmokeTest::expectException(ExceptionDuplicateKey::class, $sameItemTwice, "'si_1'");

    # New items have no ID yet, so any number can be added.
    $item = SubscriptionItem::createUsingExistingPrice('price_123', 1);
    $additions = SubscriptionItemChange::createAddition($item);
    SmokeTest::assertSame(2, count(new SubscriptionItemChangeCollection($additions, $additions)), "two additions");
});

$smoke->run("previewNewSubscription", function () use ($client, $fakeHttpClient): void {
    $items = new SubscriptionLineItemCollection(SubscriptionItem::createUsingExistingPrice('price_123', 2));
    $client->previewNewSubscription(SubscriptionCustomer::createUsingCustomerId('cus_123'), $items);
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/invoices/create_preview', $request->getPath(), "path");

    $expected = [
        'customer' => 'cus_123',
        'subscription_details' => ['items' => [['quantity' => 2, 'price' => 'price_123']]],
    ];

    SmokeTest::assertSame($expected, $request->getParams(), "params");

    $noItems = function () use ($client): void {
        $customer = SubscriptionCustomer::createUsingCustomerId('cus_123');
        $client->previewNewSubscription($customer, new SubscriptionLineItemCollection());
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $noItems, "at least one item");
});
