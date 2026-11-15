<?php

/*
 * Smoke tests for StripeClient. These are run by testing/main.php, which provides $smoke and $fakeHttpClient. Each test
 * calls a method, and checks the request that would have been sent to Stripe.
 */

use Programster\Stripe\Collections\CustomerTaxIdDataCollection;
use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\Collections\DiscountCollection;
use Programster\Stripe\Collections\LocaleCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\PaymentMethodTypeCollection;
use Programster\Stripe\Collections\SinglePaymentLineItemsCollection;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Collections\SubscriptionCheckoutLineItemCollection;
use Programster\Stripe\Collections\SubscriptionLineItemCollection;
use Programster\Stripe\Enums\CountryCode;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\CustomerCreation;
use Programster\Stripe\Enums\InvoiceStatus;
use Programster\Stripe\Enums\Locale;
use Programster\Stripe\Enums\PaymentMethodType;
use Programster\Stripe\Enums\ReconciliationMode;
use Programster\Stripe\Enums\RecurringInterval;
use Programster\Stripe\Enums\SubscriptionCollectionMethod;
use Programster\Stripe\Enums\SubscriptionStatus;
use Programster\Stripe\Enums\TaxIdDataType;
use Programster\Stripe\Enums\Updateable;
use Programster\Stripe\Enums\ValidateLocation;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Testing\FakeStripeHttpClient;
use Programster\Stripe\Exceptions\ExceptionTooManyResults;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Exceptions\ExceptionNoRelatedObject;
use Programster\Stripe\Exceptions\ExceptionWrongStatus;
use Programster\Stripe\Models\Address;
use Programster\Stripe\Models\AutomaticTax;
use Programster\Stripe\Models\BillingCycleAnchor;
use Programster\Stripe\Models\CancelAt;
use Programster\Stripe\Models\CancellationDetails;
use Programster\Stripe\Models\CustomerInvoiceSettings;
use Programster\Stripe\Models\CustomerShippingDetails;
use Programster\Stripe\Models\CustomerTaxConfig;
use Programster\Stripe\Models\CustomerTaxIdData;
use Programster\Stripe\Models\CustomerUpdateConfig;
use Programster\Stripe\Models\CustomField;
use Programster\Stripe\Models\Discount;
use Programster\Stripe\Models\DropdownOption;
use Programster\Stripe\Models\EmbeddedFlow;
use Programster\Stripe\Models\FieldType;
use Programster\Stripe\Models\HostedFlow;
use Programster\Stripe\Models\MetadataItem;
use Programster\Stripe\Models\PriceDataForSinglePayment;
use Programster\Stripe\Models\PriceDataForSubscriptionCheckoutSession;
use Programster\Stripe\Models\PriceDataForSubscriptionItem;
use Programster\Stripe\Models\ProductData;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SinglePaymentCheckoutCustomer;
use Programster\Stripe\Models\SinglePaymentLineItem;
use Programster\Stripe\Models\SubscriptionCheckoutCustomer;
use Programster\Stripe\Models\SubscriptionCheckoutLineItem;
use Programster\Stripe\Models\SubscriptionCustomer;
use Programster\Stripe\Models\SubscriptionData;
use Programster\Stripe\Models\SubscriptionItem;
use Programster\Stripe\Models\SubscriptionItemDiscount;
use Programster\Stripe\Models\SubscriptionItemPrice;
use Programster\Stripe\Models\ResultLimit;
use Programster\Stripe\Models\TimePeriod;
use Programster\Stripe\Models\SubscriptionTransferData;
use Programster\Stripe\StripeClient;
use Programster\Stripe\Responses\NewEmbeddedCheckoutSession;
use Programster\Stripe\Responses\NewHostedCheckoutSession;
use Programster\Stripe\Testing\SmokeTest;

/** @var \Programster\Stripe\Testing\SmokeTest $smoke */
/** @var \Programster\Stripe\Testing\FakeStripeHttpClient $fakeHttpClient */

$client = new StripeClient('sk_test_client_one');

$smoke->run("each client uses its own key, including for checkout sessions", function () use ($fakeHttpClient): void {
    $first = new StripeClient('sk_test_first');
    $second = new StripeClient('sk_test_second');
    $first->retrieveCheckoutSession('cs_123');
    SmokeTest::assertSame('sk_test_first', $fakeHttpClient->takeLastRequest()->getApiKey(), "retrieve's key");

    $items = new SinglePaymentLineItemsCollection(SinglePaymentLineItem::createUsingExistingPrice('price_123', 1));
    $first->createHostedCheckoutSessionForSinglePayment(new HostedFlow('https://example.com/done'), $items);
    SmokeTest::assertSame('sk_test_first', $fakeHttpClient->takeLastRequest()->getApiKey(), "create's key");
    $second->retrieveCheckoutSession('cs_123');
    SmokeTest::assertSame('sk_test_second', $fakeHttpClient->takeLastRequest()->getApiKey(), "second key");
});

$smoke->run("the secret key isn't shown by var_dump or print_r", function (): void {
    $output = print_r(new StripeClient('sk_test_do_not_show'), true);
    assert(!str_contains($output, 'sk_test_do_not_show'), "print_r showed the key: {$output}");
});

$smoke->run("listSubscriptions", function () use ($client, $fakeHttpClient): void {
    $client->listSubscriptions(
        customerId: 'cus_123',
        created: new TimePeriod(new DateTimeImmutable('@100'), new DateTimeImmutable('@200')),
        subscriptionStatus: SubscriptionStatus::CANCELLED,
        automaticTaxEnabled: true,
        collectionMethod: SubscriptionCollectionMethod::SEND_INVOICE,
        limit: ResultLimit::createUpTo(10),
    );

    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/subscriptions', $request->getPath(), "path");

    $expected = [
        'customer' => 'cus_123',
        'created' => ['gte' => 100, 'lte' => 200],
        'status' => 'canceled',
        'automatic_tax' => ['enabled' => 'true'],
        'collection_method' => 'send_invoice',
        'limit' => 10,
    ];

    SmokeTest::assertSame($expected, $request->getParams(), "params");
});

$smoke->run("createCustomer", function () use ($client, $fakeHttpClient): void {
    $address = new Address('1 High Street', city: 'London', postcodeOrZip: 'N1 1AA', country: CountryCode::GB);
    $shipping = new CustomerShippingDetails($address, 'Jo Bloggs');
    $taxIds = new CustomerTaxIdDataCollection(new CustomerTaxIdData(TaxIdDataType::GB_VAT, 'GB123456789'));

    $client->createCustomer(
        address: $address,
        email: 'jo@example.com',
        metadata: new Metadata(new MetadataItem('ref', '42')),
        shippingDetails: $shipping,
        taxConfig: new CustomerTaxConfig('203.0.113.1', ValidateLocation::IMMEDIATELY),
        cashBalanceReconciliationMode: ReconciliationMode::MANUAL,
        invoiceSettings: new CustomerInvoiceSettings(footer: 'Thanks'),
        preferredLocales: new LocaleCollection(Locale::ENGLISH),
        taxIdData: $taxIds,
    );

    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/customers', $request->getPath(), "path");
    $params = $request->getParams();
    $expectedAddress = ['line1' => '1 High Street', 'city' => 'London', 'country' => 'GB', 'postal_code' => 'N1 1AA'];
    SmokeTest::assertSame($expectedAddress, $params['address'], "address");
    SmokeTest::assertSame(['ref' => '42'], $params['metadata'], "metadata");
    SmokeTest::assertSame(['address' => $expectedAddress, 'name' => 'Jo Bloggs'], $params['shipping'], "shipping");
    $expectedTax = ['ip_address' => '203.0.113.1', 'validate_location' => 'immediately'];
    SmokeTest::assertSame($expectedTax, $params['tax'], "tax");
    $expectedCashBalance = ['settings' => ['reconciliation_mode' => 'manual']];
    SmokeTest::assertSame($expectedCashBalance, $params['cash_balance'], "cash balance");
    SmokeTest::assertSame(['footer' => 'Thanks'], $params['invoice_settings'], "invoice settings");
    SmokeTest::assertSame(['en'], $params['preferred_locales'], "locales");
    SmokeTest::assertSame([['type' => 'gb_vat', 'value' => 'GB123456789']], $params['tax_id_data'], "tax IDs");
});

$smoke->run("listSubscriptionItems", function () use ($client, $fakeHttpClient): void {
    $items = $client->listSubscriptionItems('sub_123', limit: ResultLimit::createUpTo(5));
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/subscription_items', $request->getPath(), "path");
    SmokeTest::assertSame(['subscription' => 'sub_123', 'limit' => 5], $request->getParams(), "params");
    SmokeTest::assertSame(1, count($items), "the recorded item");
    SmokeTest::assertSame(2, $items->first()->getQuantity(), "its quantity");
});

$smoke->run("updateSubscriptionItem", function () use ($client, $fakeHttpClient): void {
    $monthly = new RecurringConfig(RecurringInterval::MONTH, 1);
    $price = new PriceDataForSubscriptionItem(Currency::GBP, 500, 'prod_123', $monthly);

    $client->updateSubscriptionItem(
        'si_123',
        price: SubscriptionItemPrice::createUsingInlinePrice($price),
        quantity: 2,
        discounts: new DiscountCollection(SubscriptionItemDiscount::createUsingCouponId('coupon_1')),
        prorationDate: new DateTimeImmutable('@1700000000'),
        taxRateIds: new StringCollection('txr_1'),
    );

    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/subscription_items/si_123', $request->getPath(), "path");
    $params = $request->getParams();
    SmokeTest::assertSame(1700000000, $params['proration_date'], "proration date");
    SmokeTest::assertSame('prod_123', SmokeTest::getNested($params, 'price_data', 'product'), "price data");
    SmokeTest::assertSame([['coupon' => 'coupon_1']], $params['discounts'], "discounts");
    SmokeTest::assertSame(['txr_1'], $params['tax_rates'], "tax rates");

    $client->updateSubscriptionItem('si_123', price: SubscriptionItemPrice::createUsingExistingPrice('price_456'));
    SmokeTest::assertSame(['price' => 'price_456'], $fakeHttpClient->takeLastRequest()->getParams(), "existing price");

    $changeToProduct = function (): void {
        SubscriptionItemPrice::createUsingExistingPrice('prod_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $changeToProduct, "product's ID");
});

$smoke->run("cancelSubscription", function () use ($client, $fakeHttpClient): void {
    $client->cancelSubscription('sub_123', new CancellationDetails('Too pricey'), invoiceNow: true);
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('delete', $request->getMethod(), "method");
    SmokeTest::assertSame('/v1/subscriptions/sub_123', $request->getPath(), "path");

    $expected = ['invoice_now' => 'true', 'prorate' => 'false', 'cancellation_details' => ['comment' => 'Too pricey']];
    SmokeTest::assertSame($expected, $request->getParams(), "params");
});

$smoke->run("listPaymentIntents", function () use ($client, $fakeHttpClient): void {
    $client->listPaymentIntents(customerId: 'cus_123');
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/payment_intents', $request->getPath(), "path");
    SmokeTest::assertSame(['customer' => 'cus_123', 'limit' => 100], $request->getParams(), "params");
});

$smoke->run("listInvoices sends the customer account", function () use ($client, $fakeHttpClient): void {
    $client->listInvoices(customerId: 'cus_123', customerAccount: 'acct_456', status: InvoiceStatus::PAID);
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/invoices', $request->getPath(), "path");
    $params = $request->getParams();
    SmokeTest::assertSame('cus_123', $params['customer'], "customer");
    SmokeTest::assertSame('acct_456', $params['customer_account'], "customer account");
    SmokeTest::assertSame('paid', $params['status'], "status");
    assert(!isset($params['expand']), "listInvoices() asked for the payments");
});

$smoke->run("listCustomers, listRefunds, listCharges", function () use ($client, $fakeHttpClient): void {
    $customers = $client->listCustomers(email: 'jo@example.com');
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/customers', $request->getPath(), "customers path");
    SmokeTest::assertSame(['email' => 'jo@example.com', 'limit' => 100], $request->getParams(), "customers params");
    SmokeTest::assertSame('jo@example.com', $customers->first()->getEmail(), "the recorded customer");

    $client->listRefunds(chargeId: 'ch_123');
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/refunds', $request->getPath(), "refunds path");
    SmokeTest::assertSame(['charge' => 'ch_123', 'limit' => 100], $request->getParams(), "refunds params");

    $client->listCharges(paymentIntentId: 'pi_123');
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/charges', $request->getPath(), "charges path");
    SmokeTest::assertSame(['payment_intent' => 'pi_123', 'limit' => 100], $request->getParams(), "charges params");
});

$smoke->run("createHostedCheckoutSessionForSinglePayment", function () use ($client, $fakeHttpClient): void {
    $shirtProduct = new ProductData('T-shirt', taxCodeId: 'txcd_123');
    $shirt = PriceDataForSinglePayment::createUsingInlineProduct(Currency::GBP, 2000, $shirtProduct);
    $items = new SinglePaymentLineItemsCollection(SinglePaymentLineItem::createUsingInlinePrice($shirt, 1));
    $dropdown = FieldType::createDropdown(null, new DropdownOption('Red', 'red'));

    $session = $client->createHostedCheckoutSessionForSinglePayment(
        new HostedFlow('https://example.com/done', cancelUrl: 'https://example.com/cancel'),
        $items,
        discount: Discount::createUsingCouponId('coupon_1'),
        customer: SinglePaymentCheckoutCustomer::createUsingEmail('jo@example.com', CustomerCreation::ALWAYS),
        automaticTax: new AutomaticTax(true),
        expiresAt: new DateTimeImmutable('@1700003600'),
        adaptivePricing: true,
        customFields: new CustomFieldCollection(new CustomField('colour', 'Colour', $dropdown, true)),
        allowedPaymentMethodTypes: new PaymentMethodTypeCollection(PaymentMethodType::AFFIRM),
    );

    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/checkout/sessions', $request->getPath(), "path");
    $params = $request->getParams();
    SmokeTest::assertSame('payment', $params['mode'], "mode");
    SmokeTest::assertSame('hosted', $params['ui_mode'], "UI mode");
    SmokeTest::assertSame('https://example.com/done', $params['success_url'], "success URL");
    SmokeTest::assertSame('https://example.com/cancel', $params['cancel_url'], "cancel URL");
    $recordedUrl = FakeStripeHttpClient::loadFixture('checkout-session-hosted')['url'];
    SmokeTest::assertSame($recordedUrl, $session->getUrl(), "the payment page's URL");
    SmokeTest::assertSame($session->getId(), $session->getSession()->getId(), "the session's ID");
    SmokeTest::assertSame('jo@example.com', $params['customer_email'], "email");
    SmokeTest::assertSame('always', $params['customer_creation'], "customer creation");
    SmokeTest::assertSame(1700003600, $params['expires_at'], "expires at");
    # Stripe rejects adaptive pricing as a bare boolean ("Invalid object"), as it's an object with an "enabled" field.
    SmokeTest::assertSame(['enabled' => 'true'], $params['adaptive_pricing'], "adaptive pricing");
    SmokeTest::assertSame([['coupon' => 'coupon_1']], $params['discounts'], "discounts");
    $taxCode = SmokeTest::getNested($params, 'line_items', 0, 'price_data', 'product_data', 'tax_code');
    SmokeTest::assertSame('txcd_123', $taxCode, "tax code");
    SmokeTest::assertSame(['affirm'], $params['payment_method_types'], "payment method types");
    SmokeTest::assertSame('true', SmokeTest::getNested($params, 'custom_fields', 0, 'optional'), "optional field");
    $options = SmokeTest::getNested($params, 'custom_fields', 0, 'dropdown', 'options');
    SmokeTest::assertSame([['label' => 'Red', 'value' => 'red']], $options, "options");
});

$smoke->run("checkout customers", function () use ($client, $fakeHttpClient): void {
    $items = new SinglePaymentLineItemsCollection(SinglePaymentLineItem::createUsingExistingPrice('price_123', 1));
    $flow = new HostedFlow('https://example.com/done');
    $customer = SinglePaymentCheckoutCustomer::create(CustomerCreation::ALWAYS);
    $client->createHostedCheckoutSessionForSinglePayment($flow, $items, customer: $customer);
    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame('always', $params['customer_creation'], "creation without an email");
    assert(!isset($params['customer']) && !isset($params['customer_email']), "a customer was sent");

    $customer = SinglePaymentCheckoutCustomer::createUsingCustomerId('cus_123');
    $client->createHostedCheckoutSessionForSinglePayment($flow, $items, customer: $customer);
    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame('cus_123', $params['customer'], "an existing customer");
    assert(!isset($params['customer_creation']), "customer creation was sent with an existing customer");

    $emailIsCustomerId = function (): void {
        SinglePaymentCheckoutCustomer::createUsingEmail('cus_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $emailIsCustomerId, "email address");

    $customerIdIsEmail = function (): void {
        SubscriptionCheckoutCustomer::createUsingCustomerId('jo@example.com');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $customerIdIsEmail, "cus_");

    $subscriberEmailIsCustomerId = function (): void {
        SubscriptionCheckoutCustomer::createUsingEmail('cus_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $subscriberEmailIsCustomerId, "email address");
});

$smoke->run("createEmbeddedCheckoutSessionForSubscription", function () use ($client, $fakeHttpClient): void {
    $recurring = new RecurringConfig(RecurringInterval::MONTH, 1);

    $price = PriceDataForSubscriptionCheckoutSession::createUsingExistingProduct(
        Currency::GBP,
        999,
        'prod_123',
        $recurring
    );

    $item = SubscriptionCheckoutLineItem::createUsingInlinePrice($price, 1);
    $items = new SubscriptionCheckoutLineItemCollection($item);
    $subscriptionData = new SubscriptionData(transferData: new SubscriptionTransferData('acct_1', 10));

    $session = $client->createEmbeddedCheckoutSessionForSubscription(
        new EmbeddedFlow('https://example.com/return'),
        $items,
        subscriptionData: $subscriptionData,
        customer: SubscriptionCheckoutCustomer::createUsingEmail('jo@example.com'),
    );

    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame('subscription', $params['mode'], "mode");
    SmokeTest::assertSame('embedded', $params['ui_mode'], "UI mode");
    SmokeTest::assertSame('https://example.com/return', $params['return_url'], "return URL");
    SmokeTest::assertSame('always', $params['redirect_on_completion'], "redirect on completion");
    assert(!isset($params['success_url']) && !isset($params['cancel_url']), "a hosted flow's URL was sent");
    SmokeTest::assertSame('redacted_client_secret', $session->getClientSecret(), "client secret");
    SmokeTest::assertSame('jo@example.com', $params['customer_email'], "customer email");
    $product = SmokeTest::getNested($params, 'line_items', 0, 'price_data', 'product');
    SmokeTest::assertSame('prod_123', $product, "product");
    $expectedTransfer = ['destination' => 'acct_1', 'amount_percent' => 10.0];
    $transferData = SmokeTest::getNested($params, 'subscription_data', 'transfer_data');
    SmokeTest::assertSame($expectedTransfer, $transferData, "transfer data");
});

$smoke->run("createSubscription", function () use ($client, $fakeHttpClient): void {
    $yearly = new RecurringConfig(RecurringInterval::YEAR, 1);
    $price = new PriceDataForSubscriptionItem(Currency::GBP, 500, 'prod_123', $yearly);

    $client->createSubscription(
        SubscriptionCustomer::createUsingCustomerId('cus_123'),
        new SubscriptionLineItemCollection(SubscriptionItem::createUsingInlinePrice($price, 1)),
        Currency::GBP,
        automaticTax: new AutomaticTax(true),
        applicationFeePercent: 12.5,
        backdateStartDate: new DateTimeImmutable('@1600000000'),
        billingCycleAnchor: BillingCycleAnchor::createOnDayOfMonth(1, hour: 9),
        cancelAt: CancelAt::createAtLongestItemPeriodEnd(),
        transferData: new SubscriptionTransferData('acct_1', 20),
    );

    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/subscriptions', $request->getPath(), "path");
    $params = $request->getParams();
    SmokeTest::assertSame('cus_123', $params['customer'], "customer");
    SmokeTest::assertSame(['enabled' => 'true'], $params['automatic_tax'], "automatic tax");
    SmokeTest::assertSame(12.5, $params['application_fee_percent'], "application fee percent");
    SmokeTest::assertSame('max_period_end', $params['cancel_at'], "cancel at");
    SmokeTest::assertSame(1600000000, $params['backdate_start_date'], "backdate start date");
    $expectedAnchor = ['day_of_month' => 1, 'hour' => 9];
    SmokeTest::assertSame($expectedAnchor, $params['billing_cycle_anchor_config'], "billing cycle anchor");
    SmokeTest::assertSame(['destination' => 'acct_1', 'amount_percent' => 20.0], $params['transfer_data'], "transfer");
});

$smoke->run("a customer can be given instead of its ID", function () use ($client): void {
    $customer = $client->retrieveCustomer('cus_123');
    $customerId = $customer->getId();
    $update = new CustomerUpdateConfig(address: Updateable::AUTO);
    $expectedUpdate = ['address' => 'auto', 'name' => 'never', 'shipping' => 'never'];

    $single = SinglePaymentCheckoutCustomer::createUsingCustomer($customer, $update)->getStripeParams();
    $expected = ['customer' => $customerId, 'customer_update' => $expectedUpdate];
    SmokeTest::assertSame($expected, $single, "single payment checkout");

    $subscriber = SubscriptionCheckoutCustomer::createUsingCustomer($customer)->getStripeParams();
    SmokeTest::assertSame(['customer' => $customerId], $subscriber, "subscription checkout");

    $subscription = SubscriptionCustomer::createUsingCustomer($customer)->getStripeParams();
    SmokeTest::assertSame(['customer' => $customerId], $subscription, "subscription");
});

$smoke->run("createSubscription's customer, cancellation and anchor", function () use ($client, $fakeHttpClient): void {
    $items = new SubscriptionLineItemCollection(SubscriptionItem::createUsingExistingPrice('price_123', 1));
    $anchor = BillingCycleAnchor::createAt(new DateTimeImmutable('@1800000000'));
    $customer = SubscriptionCustomer::createUsingCustomerAccountId('acct_123');
    $client->createSubscription($customer, $items, Currency::GBP, billingCycleAnchor: $anchor);
    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame('acct_123', $params['customer_account'], "customer account");
    assert(!isset($params['customer']), "a customer was sent as well as the customer account");
    SmokeTest::assertSame(1800000000, $params['billing_cycle_anchor'], "anchor time");

    $customer = SubscriptionCustomer::createUsingCustomerId('cus_123');
    $client->createSubscription($customer, $items, Currency::GBP, cancelAt: CancelAt::createAtPeriodEnd());
    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame('true', $params['cancel_at_period_end'], "period end");
    $cancelAt = CancelAt::createAt(new DateTimeImmutable('@1900000000'));
    $client->createSubscription($customer, $items, Currency::GBP, cancelAt: $cancelAt);
    SmokeTest::assertSame(1900000000, $fakeHttpClient->takeLastRequest()->getParams()['cancel_at'], "a time");
    $client->createSubscription($customer, $items, Currency::GBP, cancelAt: CancelAt::createAtShortestItemPeriodEnd());
    SmokeTest::assertSame('min_period_end', $fakeHttpClient->takeLastRequest()->getParams()['cancel_at'], "shortest");

    $customerIdAsAccount = function (): void {
        SubscriptionCustomer::createUsingCustomerAccountId('cus_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $customerIdAsAccount, "acct_");

    $accountAsCustomer = function (): void {
        SubscriptionCustomer::createUsingCustomerId('acct_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $accountAsCustomer, "cus_");
});

$smoke->run("listCustomerPaymentMethods", function () use ($client, $fakeHttpClient): void {
    $client->listCustomerPaymentMethods('cus_123', PaymentMethodType::CARD);
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/customers/cus_123/payment_methods', $request->getPath(), "path");
    SmokeTest::assertSame(['type' => 'card', 'limit' => 100], $request->getParams(), "params");
});

$smoke->run("idempotency keys are sent, and checked", function () use ($client, $fakeHttpClient): void {
    $client->createCustomer(email: 'jo@example.com', idempotencyKey: 'order-42');
    SmokeTest::assertSame('order-42', $fakeHttpClient->takeLastRequest()->getHeader('Idempotency-Key'), "the key");
    $client->createCustomer(email: 'jo@example.com');
    assert(!$fakeHttpClient->takeLastRequest()->hasHeader('Idempotency-Key'), "a key was sent when none was given");
    $items = new SinglePaymentLineItemsCollection(SinglePaymentLineItem::createUsingExistingPrice('price_123', 1));
    $flow = new HostedFlow('https://example.com/done');
    $client->createHostedCheckoutSessionForSinglePayment($flow, $items, idempotencyKey: 'basket-7');
    SmokeTest::assertSame('basket-7', $fakeHttpClient->takeLastRequest()->getHeader('Idempotency-Key'), "checkout");

    $useEmptyKey = function () use ($client): void {
        $client->createCustomer(idempotencyKey: '');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $useEmptyKey, "1 to 255");

    $useLongKey = function () use ($client): void {
        $client->createCustomer(idempotencyKey: str_repeat('k', 256));
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $useLongKey, "1 to 255");
});

# Make a page of a customer list, of copies of the recorded customer, numbered so each has its own ID.
$makeCustomerPage = function (int $firstNumber, int $count, bool $hasMore): array {
    $customers = [];

    for ($number = $firstNumber; $number < $firstNumber + $count; $number++)
    {
        $customer = FakeStripeHttpClient::loadFixture('customer');
        $customer['id'] = "cus_{$number}";
        $customers[] = $customer;
    }

    return ['object' => 'list', 'data' => $customers, 'has_more' => $hasMore, 'url' => '/v1/customers'];
};

$smoke->run("lists get every page, carrying on from the last object", function () use (
    $client,
    $fakeHttpClient,
    $makeCustomerPage
): void {
    $fakeHttpClient->queueResponse($makeCustomerPage(1, 100, true));
    $fakeHttpClient->queueResponse($makeCustomerPage(101, 100, true));
    $fakeHttpClient->queueResponse($makeCustomerPage(201, 30, false));
    $customers = $client->listCustomers(email: 'jo@example.com');
    SmokeTest::assertSame(230, count($customers), "every customer");
    SmokeTest::assertSame('cus_230', $customers->last()->getId(), "the last customer");
    $lastRequest = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('cus_200', $lastRequest->getParams()['starting_after'], "the last page carried on");
    SmokeTest::assertSame('jo@example.com', $lastRequest->getParams()['email'], "the last page kept the filter");
});

$smoke->run("lists throw rather than quietly stop at the safety cap", function () use (
    $client,
    $fakeHttpClient,
    $makeCustomerPage
): void {
    for ($page = 0; $page < 11; $page++)
    {
        $fakeHttpClient->queueResponse($makeCustomerPage($page * 100 + 1, 100, true));
    }

    $listEveryone = function () use ($client): void {
        $client->listCustomers();
    };

    SmokeTest::expectException(ExceptionTooManyResults::class, $listEveryone, "More than 1000 customers");
});

$smoke->run("lists stop quietly at a limit that was asked for", function () use (
    $client,
    $fakeHttpClient,
    $makeCustomerPage
): void {
    $fakeHttpClient->queueResponse($makeCustomerPage(1, 100, true));
    $fakeHttpClient->queueResponse($makeCustomerPage(101, 100, true));
    $customers = $client->listCustomers(limit: ResultLimit::createUpTo(150));
    SmokeTest::assertSame(150, count($customers), "the first 150");

    for ($page = 0; $page < 12; $page++)
    {
        $fakeHttpClient->queueResponse($makeCustomerPage($page * 100 + 1, 100, $page < 11));
    }

    SmokeTest::assertSame(1200, count($client->listCustomers(limit: ResultLimit::createAll())), "every customer");

    $useZeroLimit = function (): void {
        ResultLimit::createUpTo(0);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $useZeroLimit, "at least 1");
});

$smoke->run("create, retrieve and fetch methods return the response models", function () use (
    $client,
    $fakeHttpClient
): void {
    $customer = $client->createCustomer(email: 'jo@example.com');
    SmokeTest::assertSame('Jo Bloggs', $customer->getName(), "createCustomer()");
    SmokeTest::assertSame('London', $customer->getAddress()?->getCity(), "the customer's address");
    $subscription = $client->retrieveSubscription('sub_123');
    assert($subscription->isActive(), "the recorded subscription isn't active");
    SmokeTest::assertSame(1, count($subscription->getItems()), "its items");
    $fetched = $subscription->fetchCustomer();

    SmokeTest::assertSame(
        '/v1/customers/' . $subscription->getCustomerId(),
        $fakeHttpClient->takeLastRequest()->getPath(),
        "fetchCustomer()"
    );

    SmokeTest::assertSame('jo@example.com', $fetched->getEmail(), "the fetched customer");
    $fakeHttpClient->queueResponse(FakeStripeHttpClient::loadFixture('customer-deleted'));

    $retrieveDeleted = function () use ($client): void {
        $client->retrieveCustomer('cus_123');
    };

    SmokeTest::expectException(ExceptionCustomerDeleted::class, $retrieveDeleted, "has been deleted");
});

$smoke->run("the client and its models can't be serialized", function () use ($client): void {
    $serializeClient = function () use ($client): void {
        serialize($client);
    };

    SmokeTest::expectException(\LogicException::class, $serializeClient, "secret key");

    $serializeCustomer = function () use ($client): void {
        serialize($client->retrieveCustomer('cus_123'));
    };

    SmokeTest::expectException(\LogicException::class, $serializeCustomer, "secret key");
});

$smoke->run("listInvoicesWithPayments includes the payments, and Invoice can fetch them", function () use (
    $client,
    $fakeHttpClient
): void {
    $withPayments = FakeStripeHttpClient::loadFixture('invoice-with-payments');
    $fakeHttpClient->queueResponse(['object' => 'list', 'data' => [$withPayments], 'has_more' => false, 'url' => '/']);
    $invoices = $client->listInvoicesWithPayments(customerId: 'cus_123');
    SmokeTest::assertSame(['data.payments'], $fakeHttpClient->takeLastRequest()->getParams()['expand'], "expand");
    $payment = $invoices->first()->getPayments()->first();
    SmokeTest::assertSame('pi_3UbfGaQwd7RuG7g30d6zWwi8', $payment->getPaymentIntentId(), "the payment's intent");
    SmokeTest::assertSame(1998, $payment->getAmountPaid()?->getAmount(), "the amount paid");

    $invoice = $client->retrieveInvoice('in_123');
    $recordedPayments = $withPayments['payments'] ?? null;
    assert(is_array($recordedPayments) && isset($recordedPayments['data']), "the recorded invoice has no payments");

    $fakeHttpClient->queueResponse(
        ['object' => 'list', 'data' => $recordedPayments['data'], 'has_more' => false, 'url' => '/']
    );

    $fetched = $invoice->fetchPayments();

    SmokeTest::assertSame(
        ['invoice' => $invoice->getId(), 'limit' => 100],
        $fakeHttpClient->takeLastRequest()->getParams(),
        "fetchPayments()"
    );

    SmokeTest::assertSame(1, count($fetched), "the fetched payments");

    $invoiceWithoutPayments = FakeStripeHttpClient::loadFixture('invoice');

    $fakeHttpClient->queueResponse(
        ['object' => 'list', 'data' => [$invoiceWithoutPayments], 'has_more' => false, 'url' => '/']
    );

    $listWithoutPayments = function () use ($client): void {
        $client->listInvoicesWithPayments();
    };

    SmokeTest::expectException(ExceptionUnexpectedResponse::class, $listWithoutPayments, "payments");
});

$smoke->run("the payment models read what Stripe sent", function () use ($client): void {
    $invoice = $client->retrieveInvoice('in_123');
    assert($invoice->isPaid(), "the recorded invoice isn't paid");
    SmokeTest::assertSame(1998, $invoice->getAmountPaid()->getAmount(), "the invoice's amount paid");
    assert($invoice->getAmountPaid()->hasCurrency(Currency::GBP), "the invoice isn't in pounds");
    assert($invoice->getSubscriptionId() !== null, "the invoice has no subscription");
    $paymentIntent = $client->listPaymentIntents()->first();
    assert($paymentIntent->isSucceeded(), "the payment intent didn't succeed");
    $charge = $client->listCharges()->first();
    assert($charge->isSucceeded() && $charge->isPaid(), "the charge didn't succeed");
    SmokeTest::assertSame($paymentIntent->getLatestChargeId(), $charge->getId(), "the payment intent's charge");
    $refund = $client->listRefunds()->first();
    SmokeTest::assertSame(500, $refund->getAmount()->getAmount(), "the refund's amount");
    $card = $client->listCustomerPaymentMethods('cus_123')->first();
    assert($card->hasType(PaymentMethodType::CARD), "the payment method isn't a card");
    SmokeTest::assertSame('4242', $card->getCard()?->getLast4(), "the card's last 4 digits");
});

$smoke->run("the retrieve methods ask for the object by its ID", function () use ($client, $fakeHttpClient): void {
    $client->retrievePaymentIntent('pi_123');
    SmokeTest::assertSame('/v1/payment_intents/pi_123', $fakeHttpClient->takeLastRequest()->getPath(), "intent");
    $client->retrieveCharge('ch_123');
    SmokeTest::assertSame('/v1/charges/ch_123', $fakeHttpClient->takeLastRequest()->getPath(), "charge");
    $client->retrieveRefund('re_123');
    SmokeTest::assertSame('/v1/refunds/re_123', $fakeHttpClient->takeLastRequest()->getPath(), "refund");
    $paymentMethod = $client->retrievePaymentMethod('pm_123');
    SmokeTest::assertSame('/v1/payment_methods/pm_123', $fakeHttpClient->takeLastRequest()->getPath(), "method");
    assert($paymentMethod->hasType(PaymentMethodType::CARD), "the payment method isn't a card");
});

$smoke->run("the fetch methods ask Stripe for the related objects", function () use ($client, $fakeHttpClient): void {
    $takeLastPath = function () use ($fakeHttpClient): string {
        return $fakeHttpClient->takeLastRequest()->getPath();
    };

    $subscription = $client->retrieveSubscription('sub_123');
    $subscription->fetchLatestInvoice();
    SmokeTest::assertSame('/v1/invoices/' . $subscription->getLatestInvoiceId(), $takeLastPath(), "latest invoice");
    $subscription->fetchDefaultPaymentMethod();
    $defaultPaymentMethodPath = '/v1/payment_methods/' . $subscription->getDefaultPaymentMethodId();
    SmokeTest::assertSame($defaultPaymentMethodPath, $takeLastPath(), "default payment method");

    $paymentIntent = $client->retrievePaymentIntent('pi_123');
    $paymentIntent->fetchLatestCharge();
    SmokeTest::assertSame('/v1/charges/' . $paymentIntent->getLatestChargeId(), $takeLastPath(), "latest charge");
    $paymentIntent->fetchPaymentMethod();
    SmokeTest::assertSame('/v1/payment_methods/' . $paymentIntent->getPaymentMethodId(), $takeLastPath(), "method");
    $paymentIntent->fetchCharges();
    $intentFilter = ['payment_intent' => $paymentIntent->getId(), 'limit' => 100];
    SmokeTest::assertSame($intentFilter, $fakeHttpClient->takeLastRequest()->getParams(), "the intent's charges");
    $paymentIntent->fetchRefunds();
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/refunds', $request->getPath(), "the intent's refunds");
    SmokeTest::assertSame($intentFilter, $request->getParams(), "the intent's refunds' filter");

    $charge = $client->retrieveCharge('ch_123');
    $charge->fetchCustomer();
    SmokeTest::assertSame('/v1/customers/' . $charge->getCustomerId(), $takeLastPath(), "the charge's customer");
    $charge->fetchPaymentIntent();
    SmokeTest::assertSame('/v1/payment_intents/' . $charge->getPaymentIntentId(), $takeLastPath(), "the intent");
    $charge->fetchPaymentMethod();
    SmokeTest::assertSame('/v1/payment_methods/' . $charge->getPaymentMethodId(), $takeLastPath(), "the method");
    $charge->fetchRefunds();
    $chargeFilter = ['charge' => $charge->getId(), 'limit' => 100];
    SmokeTest::assertSame($chargeFilter, $fakeHttpClient->takeLastRequest()->getParams(), "the charge's refunds");

    $refund = $client->retrieveRefund('re_123');
    $refund->fetchCharge();
    SmokeTest::assertSame('/v1/charges/' . $refund->getChargeId(), $takeLastPath(), "the refund's charge");
    $refund->fetchPaymentIntent();
    SmokeTest::assertSame('/v1/payment_intents/' . $refund->getPaymentIntentId(), $takeLastPath(), "its intent");

    $customer = $client->retrieveCustomer('cus_123');
    $customer->fetchSubscriptions();
    $everySubscription = ['customer' => $customer->getId(), 'status' => 'all', 'limit' => 100];
    SmokeTest::assertSame($everySubscription, $fakeHttpClient->takeLastRequest()->getParams(), "subscriptions");
    $customer->fetchPaymentMethods();
    $customerPaymentMethodsPath = '/v1/customers/' . $customer->getId() . '/payment_methods';
    SmokeTest::assertSame($customerPaymentMethodsPath, $takeLastPath(), "the customer's payment methods");

    $withPayments = FakeStripeHttpClient::loadFixture('invoice-with-payments');
    $fakeHttpClient->queueResponse(['object' => 'list', 'data' => [$withPayments], 'has_more' => false, 'url' => '/']);
    $payment = $client->listInvoicesWithPayments()->first()->getPayments()->first();
    $payment->fetchInvoice();
    SmokeTest::assertSame('/v1/invoices/' . $payment->getInvoiceId(), $takeLastPath(), "the payment's invoice");
    $payment->fetchPaymentIntent();
    SmokeTest::assertSame('/v1/payment_intents/' . $payment->getPaymentIntentId(), $takeLastPath(), "its intent");

    # The recorded session is still open, so it has neither yet.
    $session = $client->retrieveCheckoutSession('cs_123');

    $fetchPaymentIntent = function () use ($session): void {
        $session->fetchPaymentIntent();
    };

    SmokeTest::expectException(ExceptionNoRelatedObject::class, $fetchPaymentIntent, "no payment intent");

    $fetchInvoice = function () use ($session): void {
        $session->fetchInvoice();
    };

    SmokeTest::expectException(ExceptionNoRelatedObject::class, $fetchInvoice, "no invoice");
});

$smoke->run("a new session is certain to have its URL or client secret", function () use (
    $client,
    $fakeHttpClient
): void {
    $hostedSession = $client->retrieveCheckoutSession('cs_123');
    $hosted = new NewHostedCheckoutSession($hostedSession);
    SmokeTest::assertSame($hostedSession->getUrl(), $hosted->getUrl(), "the URL");

    $wrapHostedAsEmbedded = function () use ($hostedSession): void {
        new NewEmbeddedCheckoutSession($hostedSession);
    };

    SmokeTest::expectException(ExceptionUnexpectedResponse::class, $wrapHostedAsEmbedded, "no client secret");
    $fakeHttpClient->queueResponse(FakeStripeHttpClient::loadFixture('checkout-session-embedded'));
    $embeddedSession = $client->retrieveCheckoutSession('cs_123');

    $wrapEmbeddedAsHosted = function () use ($embeddedSession): void {
        new NewHostedCheckoutSession($embeddedSession);
    };

    SmokeTest::expectException(ExceptionUnexpectedResponse::class, $wrapEmbeddedAsHosted, "no URL");
});

$smoke->run("only a completed session can be a CompletedCheckoutSession", function () use (
    $client,
    $fakeHttpClient
): void {
    $openSession = $client->retrieveCheckoutSession('cs_123');

    $completeOpenSession = function () use ($openSession): void {
        $openSession->toCompleted();
    };

    SmokeTest::expectException(ExceptionWrongStatus::class, $completeOpenSession, "is open");

    # Stripe empties the URL once a session is completed.
    $completed = FakeStripeHttpClient::loadFixture('checkout-session-hosted');
    $completed['status'] = 'complete';
    $completed['url'] = null;
    $fakeHttpClient->queueResponse($completed);
    $completedSession = $client->retrieveCheckoutSession('cs_123')->toCompleted();
    SmokeTest::assertSame('jo@example.com', $completedSession->getCustomerDetails()->getEmail(), "the email");
    SmokeTest::assertSame(null, $completedSession->getSession()->getUrl(), "the emptied URL");

    $completed['customer_details'] = null;
    $fakeHttpClient->queueResponse($completed);
    $sessionWithoutDetails = $client->retrieveCheckoutSession('cs_123');

    $completeWithoutDetails = function () use ($sessionWithoutDetails): void {
        $sessionWithoutDetails->toCompleted();
    };

    SmokeTest::expectException(ExceptionUnexpectedResponse::class, $completeWithoutDetails, "no customer details");
});
