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
use Programster\Stripe\Enums\ValidateLocation;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Testing\FakeStripeHttpClient;
use Programster\Stripe\Exceptions\ExceptionTooManyResults;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Models\Address;
use Programster\Stripe\Models\AutomaticTax;
use Programster\Stripe\Models\BillingCycleAnchor;
use Programster\Stripe\Models\CancelAt;
use Programster\Stripe\Models\CancellationDetails;
use Programster\Stripe\Models\CustomerInvoiceSettings;
use Programster\Stripe\Models\CustomerShippingDetails;
use Programster\Stripe\Models\CustomerTaxConfig;
use Programster\Stripe\Models\CustomerTaxIdData;
use Programster\Stripe\Models\CustomField;
use Programster\Stripe\Models\Discount;
use Programster\Stripe\Models\DropdownOption;
use Programster\Stripe\Models\FieldType;
use Programster\Stripe\Models\FlowConfig;
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
use Programster\Stripe\Testing\SmokeTest;

/** @var \Programster\Stripe\Testing\SmokeTest $smoke */
/** @var \Programster\Stripe\Testing\FakeStripeHttpClient $fakeHttpClient */

$client = new StripeClient('sk_test_client_one');

$smoke->run("each client uses its own key, including for checkout sessions", function () use ($fakeHttpClient): void {
    $first = new StripeClient('sk_test_first');
    $second = new StripeClient('sk_test_second');
    $first->retrieveCheckoutSession('cs_123');
    SmokeTest::assertSame('sk_test_first', $fakeHttpClient->takeLastRequest()->getApiKey(), "retrieve's key");

    $items = new SinglePaymentLineItemsCollection(SinglePaymentLineItem::forPrice('price_123', 1));
    $first->createCheckoutSessionForSinglePayment(FlowConfig::createHosted('https://example.com/done'), $items);
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
        limit: ResultLimit::upTo(10),
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
    $address = new Address('1 High Street', city: 'London', postcodeOrZip: 'N1 1AA', country: 'GB');
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
    $items = $client->listSubscriptionItems('sub_123', limit: ResultLimit::upTo(5));
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
        price: SubscriptionItemPrice::forPriceData($price),
        quantity: 2,
        discounts: new DiscountCollection(SubscriptionItemDiscount::createFromCouponId('coupon_1')),
        prorationDate: new DateTimeImmutable('@1700000000'),
        taxRateIds: new StringCollection('txr_1'),
    );

    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/subscription_items/si_123', $request->getPath(), "path");
    $params = $request->getParams();
    SmokeTest::assertSame(1700000000, $params['proration_date'], "proration date");
    SmokeTest::assertSame('prod_123', $params['price_data']['product'], "price data");
    SmokeTest::assertSame([['coupon' => 'coupon_1']], $params['discounts'], "discounts");
    SmokeTest::assertSame(['txr_1'], $params['tax_rates'], "tax rates");

    $client->updateSubscriptionItem('si_123', price: SubscriptionItemPrice::forPrice('price_456'));
    SmokeTest::assertSame(['price' => 'price_456'], $fakeHttpClient->takeLastRequest()->getParams(), "existing price");

    $changeToProduct = function (): void {
        SubscriptionItemPrice::forPrice('prod_123');
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

    $client->listCharges(paymentIntent: 'pi_123');
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/charges', $request->getPath(), "charges path");
    SmokeTest::assertSame(['payment_intent' => 'pi_123', 'limit' => 100], $request->getParams(), "charges params");
});

$smoke->run("createCheckoutSessionForSinglePayment", function () use ($client, $fakeHttpClient): void {
    $shirtProduct = new ProductData('T-shirt', taxCodeId: 'txcd_123');
    $shirt = PriceDataForSinglePayment::forProductData(Currency::GBP, 2000, $shirtProduct);
    $items = new SinglePaymentLineItemsCollection(SinglePaymentLineItem::forPriceData($shirt, 1));
    $dropdown = FieldType::createDropdown(null, new DropdownOption('Red', 'red'));

    $client->createCheckoutSessionForSinglePayment(
        FlowConfig::createHosted('https://example.com/done', cancelUrl: 'https://example.com/cancel'),
        $items,
        discount: Discount::createCoupon('coupon_1'),
        customer: SinglePaymentCheckoutCustomer::withEmail('jo@example.com', CustomerCreation::ALWAYS),
        automaticTax: new AutomaticTax(true),
        expiresAt: new DateTimeImmutable('@1700003600'),
        customFields: new CustomFieldCollection(new CustomField('colour', 'Colour', $dropdown, true)),
        allowedPaymentMethodTypes: new PaymentMethodTypeCollection(PaymentMethodType::AFFIRM),
    );

    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/checkout/sessions', $request->getPath(), "path");
    $params = $request->getParams();
    SmokeTest::assertSame('payment', $params['mode'], "mode");
    SmokeTest::assertSame('https://example.com/cancel', $params['cancel_url'], "cancel URL");
    SmokeTest::assertSame('jo@example.com', $params['customer_email'], "email");
    SmokeTest::assertSame('always', $params['customer_creation'], "customer creation");
    SmokeTest::assertSame(1700003600, $params['expires_at'], "expires at");
    SmokeTest::assertSame([['coupon' => 'coupon_1']], $params['discounts'], "discounts");
    SmokeTest::assertSame('txcd_123', $params['line_items'][0]['price_data']['product_data']['tax_code'], "tax code");
    SmokeTest::assertSame(['affirm'], $params['payment_method_types'], "payment method types");
    $customField = $params['custom_fields'][0];
    SmokeTest::assertSame('true', $customField['optional'], "custom field is optional");
    SmokeTest::assertSame([['label' => 'Red', 'value' => 'red']], $customField['dropdown']['options'], "options");
});

$smoke->run("checkout customers", function () use ($client, $fakeHttpClient): void {
    $items = new SinglePaymentLineItemsCollection(SinglePaymentLineItem::forPrice('price_123', 1));
    $flowConfig = FlowConfig::createHosted('https://example.com/done');
    $customer = SinglePaymentCheckoutCustomer::create(CustomerCreation::ALWAYS);
    $client->createCheckoutSessionForSinglePayment($flowConfig, $items, customer: $customer);
    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame('always', $params['customer_creation'], "creation without an email");
    assert(!isset($params['customer']) && !isset($params['customer_email']), "a customer was sent");

    $customer = SinglePaymentCheckoutCustomer::existing('cus_123');
    $client->createCheckoutSessionForSinglePayment($flowConfig, $items, customer: $customer);
    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame('cus_123', $params['customer'], "an existing customer");
    assert(!isset($params['customer_creation']), "customer creation was sent with an existing customer");

    $emailIsCustomerId = function (): void {
        SinglePaymentCheckoutCustomer::withEmail('cus_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $emailIsCustomerId, "email address");

    $customerIdIsEmail = function (): void {
        SubscriptionCheckoutCustomer::existing('jo@example.com');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $customerIdIsEmail, "cus_");

    $subscriberEmailIsCustomerId = function (): void {
        SubscriptionCheckoutCustomer::withEmail('cus_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $subscriberEmailIsCustomerId, "email address");
});

$smoke->run("createCheckoutSessionForSubscription", function () use ($client, $fakeHttpClient): void {
    $recurring = new RecurringConfig(RecurringInterval::MONTH, 1);
    $price = PriceDataForSubscriptionCheckoutSession::forProduct(Currency::GBP, 999, 'prod_123', $recurring);
    $items = new SubscriptionCheckoutLineItemCollection(SubscriptionCheckoutLineItem::forPriceData($price, 1));
    $subscriptionData = new SubscriptionData(transferData: new SubscriptionTransferData('acct_1', 10));

    $client->createCheckoutSessionForSubscription(
        FlowConfig::createEmbedded('https://example.com/return'),
        $items,
        subscriptionData: $subscriptionData,
        customer: SubscriptionCheckoutCustomer::withEmail('jo@example.com'),
    );

    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame('subscription', $params['mode'], "mode");
    SmokeTest::assertSame('embedded', $params['ui_mode'], "UI mode");
    SmokeTest::assertSame('jo@example.com', $params['customer_email'], "customer email");
    SmokeTest::assertSame('prod_123', $params['line_items'][0]['price_data']['product'], "product");
    $expectedTransfer = ['destination' => 'acct_1', 'amount_percent' => 10.0];
    SmokeTest::assertSame($expectedTransfer, $params['subscription_data']['transfer_data'], "transfer data");
});

$smoke->run("createSubscription", function () use ($client, $fakeHttpClient): void {
    $yearly = new RecurringConfig(RecurringInterval::YEAR, 1);
    $price = new PriceDataForSubscriptionItem(Currency::GBP, 500, 'prod_123', $yearly);

    $client->createSubscription(
        SubscriptionCustomer::customer('cus_123'),
        new SubscriptionLineItemCollection(SubscriptionItem::forPriceData($price, 1)),
        Currency::GBP,
        automaticTax: new AutomaticTax(true),
        applicationFeePercent: 12.5,
        backdateStartDate: new DateTimeImmutable('@1600000000'),
        billingCycleAnchor: BillingCycleAnchor::onDayOfMonth(1, hour: 9),
        cancelAt: CancelAt::atLongestItemPeriodEnd(),
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

$smoke->run("createSubscription's customer, cancellation and anchor", function () use ($client, $fakeHttpClient): void {
    $items = new SubscriptionLineItemCollection(SubscriptionItem::forPrice('price_123', 1));
    $anchor = BillingCycleAnchor::at(new DateTimeImmutable('@1800000000'));
    $customer = SubscriptionCustomer::customerAccount('acct_123');
    $client->createSubscription($customer, $items, Currency::GBP, billingCycleAnchor: $anchor);
    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame('acct_123', $params['customer_account'], "customer account");
    assert(!isset($params['customer']), "a customer was sent as well as the customer account");
    SmokeTest::assertSame(1800000000, $params['billing_cycle_anchor'], "anchor time");

    $customer = SubscriptionCustomer::customer('cus_123');
    $client->createSubscription($customer, $items, Currency::GBP, cancelAt: CancelAt::atPeriodEnd());
    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame('true', $params['cancel_at_period_end'], "period end");
    $cancelAt = CancelAt::at(new DateTimeImmutable('@1900000000'));
    $client->createSubscription($customer, $items, Currency::GBP, cancelAt: $cancelAt);
    SmokeTest::assertSame(1900000000, $fakeHttpClient->takeLastRequest()->getParams()['cancel_at'], "a time");
    $client->createSubscription($customer, $items, Currency::GBP, cancelAt: CancelAt::atShortestItemPeriodEnd());
    SmokeTest::assertSame('min_period_end', $fakeHttpClient->takeLastRequest()->getParams()['cancel_at'], "shortest");

    $customerIdAsAccount = function (): void {
        SubscriptionCustomer::customerAccount('cus_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $customerIdAsAccount, "acct_");

    $accountAsCustomer = function (): void {
        SubscriptionCustomer::customer('acct_123');
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
    $items = new SinglePaymentLineItemsCollection(SinglePaymentLineItem::forPrice('price_123', 1));
    $flowConfig = FlowConfig::createHosted('https://example.com/done');
    $client->createCheckoutSessionForSinglePayment($flowConfig, $items, idempotencyKey: 'basket-7');
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
    $customers = $client->listCustomers(limit: ResultLimit::upTo(150));
    SmokeTest::assertSame(150, count($customers), "the first 150");

    for ($page = 0; $page < 12; $page++)
    {
        $fakeHttpClient->queueResponse($makeCustomerPage($page * 100 + 1, 100, $page < 11));
    }

    SmokeTest::assertSame(1200, count($client->listCustomers(limit: ResultLimit::all())), "every customer");

    $useZeroLimit = function (): void {
        ResultLimit::upTo(0);
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

    $fakeHttpClient->queueResponse(
        ['object' => 'list', 'data' => $withPayments['payments']['data'], 'has_more' => false, 'url' => '/']
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
