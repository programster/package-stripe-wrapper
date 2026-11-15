<?php

/*
 * Live tests for subscriptions, and for the methods that list what they create (invoices, payment intents, charges,
 * refunds and payment methods). These are run by testing/main-live.php, which provides $smoke and $fixtures. The
 * customer is on the fixtures' test clock, so deleting the clock afterwards deletes it and its subscriptions. Stripe
 * allows each customer on a test clock up to three subscriptions, so the options are spread over three.
 */

use Programster\Stripe\Collections\DiscountCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\PaymentMethodTypeCollection;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Collections\SubscriptionInvoiceItemCollection;
use Programster\Stripe\Collections\SubscriptionLineItemCollection;
use Programster\Stripe\Enums\CancellationFeedback;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\EndTrialBehaviorIfMissingPaymentMethod;
use Programster\Stripe\Enums\PaymentMethodType;
use Programster\Stripe\Enums\ProrationBehavior;
use Programster\Stripe\Enums\ProrationDiscountConfig;
use Programster\Stripe\Enums\RecurringInterval;
use Programster\Stripe\Enums\SubscriptionCollectionMethod;
use Programster\Stripe\Enums\SubscriptionStatus;
use Programster\Stripe\Models\BillingCycleAnchor;
use Programster\Stripe\Models\BillingMode;
use Programster\Stripe\Models\CancelAt;
use Programster\Stripe\Models\CancellationDetails;
use Programster\Stripe\Models\InvoiceItemPeriod;
use Programster\Stripe\Models\MetadataItem;
use Programster\Stripe\Models\PaymentSettings;
use Programster\Stripe\Models\PeriodEnd;
use Programster\Stripe\Models\PeriodStart;
use Programster\Stripe\Models\PriceDataForInvoiceItem;
use Programster\Stripe\Models\PriceDataForSubscriptionItem;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SubscriptionCustomer;
use Programster\Stripe\Models\SubscriptionInvoiceItem;
use Programster\Stripe\Models\SubscriptionItem;
use Programster\Stripe\Models\SubscriptionItemDiscount;
use Programster\Stripe\Models\TimePeriod;
use Programster\Stripe\Models\SubscriptionTransferData;
use Programster\Stripe\Models\TrialConfig;
use Programster\Stripe\Models\ResultLimit;
use Programster\Stripe\StripeClient;
use Programster\Stripe\Testing\LiveFixtures;
use Programster\Stripe\Testing\SmokeTest;

/** @var \Programster\Stripe\Testing\SmokeTest $smoke */
/** @var \Programster\Stripe\Testing\LiveFixtures $fixtures */

$client = new StripeClient(STRIPE_SECRET_KEY);
$metadata = new Metadata(new MetadataItem('created_by', LiveFixtures::METADATA['created_by']));
$monthly = new RecurringConfig(RecurringInterval::MONTH, 1);
$price = new PriceDataForSubscriptionItem(Currency::GBP, 1000, $fixtures->getProductId(), $monthly);
$items = new SubscriptionLineItemCollection(SubscriptionItem::forPriceData($price, 1));
$subscriberId = '';
$paymentMethodId = '';
$trialSubscriptionId = '';
$invoicedSubscriptionId = '';

$smoke->run("createCustomer on a test clock, and listCustomerPaymentMethods", function () use (
    $client,
    $fixtures,
    &$subscriberId,
    &$paymentMethodId
): void {
    $customer = $client->createCustomer(
        email: 'subscriber@example.com',
        paymentMethodId: 'pm_card_visa',
        testClockId: $fixtures->getTestClockId(),
    );

    $subscriberId = $customer->getId();
    $paymentMethods = $client->listCustomerPaymentMethods($customer->getId(), PaymentMethodType::CARD);
    SmokeTest::assertSame(1, count($paymentMethods), "number of payment methods");
    $paymentMethodId = $paymentMethods->first()->getId();
});

$smoke->run("createSubscription: trial, metadata and description", function () use (
    $client,
    $items,
    $metadata,
    &$subscriberId,
    &$paymentMethodId,
    &$trialSubscriptionId
): void {
    $subscription = $client->createSubscription(
        SubscriptionCustomer::customer($subscriberId),
        $items,
        Currency::GBP,
        description: 'Live test trial subscription',
        defaultPaymentMethodId: $paymentMethodId,
        metadata: $metadata,
        cancelAt: CancelAt::atPeriodEnd(),
        trialConfig: TrialConfig::createForNumDays(7, EndTrialBehaviorIfMissingPaymentMethod::CANCEL),
    );

    $trialSubscriptionId = $subscription->getId();
    SmokeTest::assertSame('trialing', $subscription->getStatus(), "status");
    SmokeTest::assertSame(true, $subscription->isCancelAtPeriodEnd(), "cancel at period end");

    SmokeTest::assertSame(
        'cancel',
        $subscription->getStripeObject()->trial_settings?->end_behavior->missing_payment_method,
        "trial end"
    );
});

$smoke->run("createSubscription: billing, discounts, tax rates and invoice items", function () use (
    $client,
    $fixtures,
    $items,
    &$subscriberId,
    &$paymentMethodId
): void {
    $invoiceItemPrice = new PriceDataForInvoiceItem(Currency::GBP, $fixtures->getProductId(), 250);
    $period = new InvoiceItemPeriod(PeriodStart::createMaxItemPeriodStart(), PeriodEnd::createMinItemPeriodEnd());
    $taxRates = new StringCollection($fixtures->getTaxRateId());
    $invoiceItem = SubscriptionInvoiceItem::forPriceData($invoiceItemPrice, 1, period: $period, taxRates: $taxRates);
    $discounts = new DiscountCollection(SubscriptionItemDiscount::createFromCouponId($fixtures->getCouponId()));
    $cardOnly = new PaymentMethodTypeCollection(PaymentMethodType::CARD);

    $subscription = $client->createSubscription(
        SubscriptionCustomer::customer($subscriberId),
        $items,
        Currency::GBP,
        defaultPaymentMethodId: $paymentMethodId,
        addInvoiceItems: new SubscriptionInvoiceItemCollection($invoiceItem),
        billingCycleAnchor: BillingCycleAnchor::onDayOfMonth(1),
        billingMode: BillingMode::createFlexible(ProrationDiscountConfig::ITEMIZED),
        cancelAt: CancelAt::atLongestItemPeriodEnd(),
        collectionMethod: SubscriptionCollectionMethod::CHARGE_AUTOMATICALLY,
        defaultTaxRates: $taxRates,
        discounts: $discounts,
        paymentSettings: new PaymentSettings(paymentMethodTypes: $cardOnly, saveDefaultPaymentMethod: true),
        prorationBehavior: ProrationBehavior::NONE,
    );

    SmokeTest::assertSame('active', $subscription->getStatus(), "status");

    SmokeTest::assertSame(
        'on_subscription',
        $subscription->getStripeObject()->payment_settings?->save_default_payment_method,
        "save"
    );

    SmokeTest::assertSame('flexible', $subscription->getStripeObject()->billing_mode->type, "billing mode");

    $taxRates = $subscription->getStripeObject()->default_tax_rates;
    assert(isset($taxRates[0]), "the subscription has no default tax rates");
    SmokeTest::assertSame($fixtures->getTaxRateId(), $taxRates[0]->id, "tax rate");
});

$smoke->run("createSubscription: sent as an invoice", function () use (
    $client,
    $items,
    &$subscriberId,
    &$invoicedSubscriptionId
): void {
    $subscription = $client->createSubscription(
        SubscriptionCustomer::customer($subscriberId),
        $items,
        Currency::GBP,
        cancelAt: CancelAt::at(new DateTimeImmutable('+90 days')),
        collectionMethod: SubscriptionCollectionMethod::SEND_INVOICE,
        daysUntilDue: 30,
        # This must be more frequent than the monthly price.
        pendingInvoiceItemInterval: new RecurringConfig(RecurringInterval::WEEK, 1),
    );

    $invoicedSubscriptionId = $subscription->getId();
    SmokeTest::assertSame('send_invoice', $subscription->getCollectionMethod(), "collection method");
    SmokeTest::assertSame(30, $subscription->getDaysUntilDue(), "days until due");
});

if (STRIPE_CONNECTED_ACCOUNT_ID !== '')
{
    $smoke->run("createSubscription: Stripe Connect transfer", function () use (
        $client,
        $fixtures,
        $items,
        $metadata
    ): void {
        # A customer of its own, without the test clock, as a fourth subscription would be over the clock's limit.
        # Deleting the customer afterwards cancels the subscription.
        $customer = $client->createCustomer(metadata: $metadata, paymentMethodId: 'pm_card_visa');
        $fixtures->trackCustomer($customer->getId());
        $paymentMethods = $client->listCustomerPaymentMethods($customer->getId(), PaymentMethodType::CARD);

        $subscription = $client->createSubscription(
            SubscriptionCustomer::customer($customer->getId()),
            $items,
            Currency::GBP,
            defaultPaymentMethodId: $paymentMethods->first()->getId(),
            transferData: new SubscriptionTransferData(STRIPE_CONNECTED_ACCOUNT_ID, 20),
        );

        SmokeTest::assertSame(
            20.0,
            (float)$subscription->getStripeObject()->transfer_data?->amount_percent,
            "amount percent"
        );
    });
}
else
{
    $smoke->skip("createSubscription: Stripe Connect transfer", "set STRIPE_CONNECTED_ACCOUNT_ID in settings.php");
}

$smoke->run("listSubscriptions with filters", function () use ($client, &$subscriberId): void {
    $subscriptions = $client->listSubscriptions(
        customerId: $subscriberId,
        created: new TimePeriod(new DateTimeImmutable('-1 day'), new DateTimeImmutable('+1 day')),
        subscriptionStatus: SubscriptionStatus::ALL,
        automaticTaxEnabled: false,
        collectionMethod: SubscriptionCollectionMethod::SEND_INVOICE,
    );

    SmokeTest::assertSame(1, count($subscriptions), "number of invoiced subscriptions");
});

$smoke->run("listSubscriptionItems and updateSubscriptionItem", function () use (
    $client,
    $fixtures,
    $metadata,
    &$trialSubscriptionId
): void {
    $subscriptionItems = $client->listSubscriptionItems($trialSubscriptionId);
    SmokeTest::assertSame(1, count($subscriptionItems), "number of items");

    $updated = $client->updateSubscriptionItem(
        $subscriptionItems->first()->getId(),
        metadata: $metadata,
        prorationBehavior: ProrationBehavior::NONE,
        quantity: 2,
        discounts: new DiscountCollection(SubscriptionItemDiscount::createFromCouponId($fixtures->getCouponId())),
        taxRateIds: new StringCollection($fixtures->getTaxRateId()),
    );

    SmokeTest::assertSame(2, $updated->getQuantity(), "quantity");
    SmokeTest::assertSame($fixtures->getTaxRateId(), $updated->getTaxRateIds()->first(), "tax rate");
});

$smoke->run("cancelSubscription with cancellation details", function () use ($client, &$trialSubscriptionId): void {
    $details = new CancellationDetails('Just testing', CancellationFeedback::TOO_EXPENSIVE);
    $subscription = $client->cancelSubscription($trialSubscriptionId, $details, prorate: true);
    SmokeTest::assertSame('canceled', $subscription->getStatus(), "status");
    SmokeTest::assertSame('too_expensive', $subscription->getCancellationFeedback(), "feedback");
});

$smoke->run("listInvoices, listPaymentIntents, listCharges and listRefunds", function () use (
    $client,
    &$subscriberId,
    &$invoicedSubscriptionId
): void {
    $invoices = $client->listInvoices(customerId: $subscriberId, subscriptionId: $invoicedSubscriptionId);
    assert(count($invoices) >= 1, "the invoiced subscription has no invoices");
    $client->listInvoices(collectionMethod: SubscriptionCollectionMethod::SEND_INVOICE);
    $client->listPaymentIntents(customerId: $subscriberId);
    $client->listCharges(customerId: $subscriberId);
    $client->listRefunds(limit: ResultLimit::upTo(1));
});

$smoke->run("fetch methods follow the links between a subscription's objects", function () use (
    $client,
    $fixtures,
    $items,
    $metadata
): void {
    # A customer of its own, without the test clock, so the subscription is charged straight away (and a fourth
    # subscription would be over the clock's limit). Deleting the customer afterwards cancels the subscription.
    $customer = $client->createCustomer(metadata: $metadata, paymentMethodId: 'pm_card_visa');
    $fixtures->trackCustomer($customer->getId());
    $paymentMethod = $customer->fetchPaymentMethods()->first();
    SmokeTest::assertSame($customer->getId(), $paymentMethod->fetchCustomer()->getId(), "the method's customer");

    $subscription = $client->createSubscription(
        SubscriptionCustomer::customer($customer->getId()),
        $items,
        Currency::GBP,
        defaultPaymentMethodId: $paymentMethod->getId(),
    );

    $defaultPaymentMethod = $subscription->fetchDefaultPaymentMethod();
    SmokeTest::assertSame($paymentMethod->getId(), $defaultPaymentMethod->getId(), "default payment method");
    SmokeTest::assertSame(1, count($customer->fetchSubscriptions()), "the customer's subscriptions");
    $invoice = $subscription->fetchLatestInvoice();
    SmokeTest::assertSame($subscription->getId(), $invoice->getSubscriptionId(), "the invoice's subscription");
    $payment = $invoice->fetchPayments()->first();
    SmokeTest::assertSame($invoice->getId(), $payment->fetchInvoice()->getId(), "the payment's invoice");
    $paymentIntent = $payment->fetchPaymentIntent();
    assert($paymentIntent->isSucceeded(), "the subscription's first payment didn't succeed");
    SmokeTest::assertSame($customer->getId(), $paymentIntent->fetchCustomer()->getId(), "the intent's customer");
    $intentPaymentMethod = $paymentIntent->fetchPaymentMethod();
    SmokeTest::assertSame($paymentMethod->getId(), $intentPaymentMethod->getId(), "the intent's payment method");
    $charge = $paymentIntent->fetchLatestCharge();
    SmokeTest::assertSame($paymentIntent->getId(), $charge->fetchPaymentIntent()->getId(), "the charge's intent");
    SmokeTest::assertSame(1, count($paymentIntent->fetchCharges()), "the intent's charges");
    SmokeTest::assertSame($customer->getId(), $charge->fetchCustomer()->getId(), "the charge's customer");
    SmokeTest::assertSame($paymentMethod->getId(), $charge->fetchPaymentMethod()->getId(), "the charge's method");

    # The package doesn't create refunds, so make one with the SDK.
    $refundParams = ['charge' => $charge->getId(), 'amount' => 100, 'metadata' => LiveFixtures::METADATA];
    $fixtures->getSdk()->refunds->create($refundParams);
    $refund = $charge->fetchRefunds()->first();
    SmokeTest::assertSame(100, $refund->getAmount()->getAmount(), "the refund's amount");
    SmokeTest::assertSame($charge->getId(), $refund->fetchCharge()->getId(), "the refund's charge");
    SmokeTest::assertSame($paymentIntent->getId(), $refund->fetchPaymentIntent()->getId(), "the refund's intent");
    SmokeTest::assertSame(1, count($paymentIntent->fetchRefunds()), "the intent's refunds");
    SmokeTest::assertSame($refund->getId(), $client->retrieveRefund($refund->getId())->getId(), "retrieveRefund()");
});
