<?php

/*
 * Smoke tests for StripeClient. These are run by testing/main.php, which provides $smoke and $fakeHttpClient. Each test
 * calls a method, and checks the request that would have been sent to Stripe.
 */

use Programster\Stripe\Collections\CustomerShippingDetailsCollection;
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
use Programster\Stripe\Enums\CancelAtEnum;
use Programster\Stripe\Enums\Currency;
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
use Programster\Stripe\Models\Address;
use Programster\Stripe\Models\AutomaticTax;
use Programster\Stripe\Models\CancellationDetails;
use Programster\Stripe\Models\CustomerInvoiceSettings;
use Programster\Stripe\Models\CustomerShippingDetails;
use Programster\Stripe\Models\CustomerTaxConfig;
use Programster\Stripe\Models\CustomerTaxIdData;
use Programster\Stripe\Models\CustomField;
use Programster\Stripe\Models\Discount;
use Programster\Stripe\Models\DropdownOption;
use Programster\Stripe\Models\ExistingCustomer;
use Programster\Stripe\Models\FieldType;
use Programster\Stripe\Models\FlowConfig;
use Programster\Stripe\Models\MetadataItem;
use Programster\Stripe\Models\PriceDataForSinglePayment;
use Programster\Stripe\Models\PriceDataForSubscriptionCheckoutSession;
use Programster\Stripe\Models\PriceDataForSubscriptionItem;
use Programster\Stripe\Models\ProductData;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SinglePaymentLineItem;
use Programster\Stripe\Models\SubscriptionCheckoutLineItem;
use Programster\Stripe\Models\SubscriptionData;
use Programster\Stripe\Models\SubscriptionItem;
use Programster\Stripe\Models\SubscriptionItemDiscount;
use Programster\Stripe\Models\TimePeriod;
use Programster\Stripe\Models\TransferData;
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

    $items = new SinglePaymentLineItemsCollection(new SinglePaymentLineItem(1, 'price_123'));
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
        created: new TimePeriod(100, 200),
        subscriptionStatus: SubscriptionStatus::CANCELLED,
        automaticTaxEnabled: true,
        collectionMethod: SubscriptionCollectionMethod::SEND_INVOICE,
        limit: 10,
    );

    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/subscriptions', $request->getPath(), "path");

    $expected = [
        'limit' => 10,
        'customer' => 'cus_123',
        'created' => ['gte' => 100, 'lte' => 200],
        'status' => 'canceled',
        'automatic_tax' => ['enabled' => 'true'],
        'collection_method' => 'send_invoice',
    ];

    SmokeTest::assertSame($expected, $request->getParams(), "params");
});

$smoke->run("createCustomer", function () use ($client, $fakeHttpClient): void {
    $address = new Address('1 High Street', city: 'London', postcodeOrZip: 'N1 1AA', country: 'GB');
    $shipping = new CustomerShippingDetailsCollection(new CustomerShippingDetails($address, 'Jo Bloggs'));
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

$smoke->run("createCustomer rejects more than one set of shipping details", function () use ($client): void {
    $address = new Address('1 High Street');

    $shipping = new CustomerShippingDetailsCollection(
        new CustomerShippingDetails($address, 'Jo'),
        new CustomerShippingDetails($address, 'Sam')
    );

    $createWithTwoAddresses = function () use ($client, $shipping): void {
        $client->createCustomer(shippingDetails: $shipping);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createWithTwoAddresses, "only have one");
});

$smoke->run("listSubscriptionItems", function () use ($client, $fakeHttpClient): void {
    $client->listSubscriptionItems('sub_123', limit: 5);
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/subscription_items', $request->getPath(), "path");
    SmokeTest::assertSame(['subscription' => 'sub_123', 'limit' => 5], $request->getParams(), "params");
});

$smoke->run("updateSubscriptionItem returns the updated item", function () use ($client, $fakeHttpClient): void {
    $monthly = new RecurringConfig(RecurringInterval::MONTH, 1);
    $price = new PriceDataForSubscriptionItem(Currency::GBP, 500, 'prod_123', $monthly);

    $item = $client->updateSubscriptionItem(
        'si_123',
        priceDataOrPriceId: $price,
        quantity: 2,
        discounts: new DiscountCollection(SubscriptionItemDiscount::createFromCouponId('coupon_1')),
        taxRateIds: new StringCollection('txr_1'),
    );

    assert($item instanceof \Stripe\SubscriptionItem, "it didn't return the item");
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/subscription_items/si_123', $request->getPath(), "path");
    $params = $request->getParams();
    SmokeTest::assertSame('prod_123', $params['price_data']['product'], "price data");
    SmokeTest::assertSame([['coupon' => 'coupon_1']], $params['discounts'], "discounts");
    SmokeTest::assertSame(['txr_1'], $params['tax_rates'], "tax rates");
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
    SmokeTest::assertSame(['limit' => 100, 'customer' => 'cus_123'], $request->getParams(), "params");
});

$smoke->run("listInvoices sends the customer account", function () use ($client, $fakeHttpClient): void {
    $client->listInvoices(customerId: 'cus_123', customerAccount: 'acct_456', status: InvoiceStatus::PAID);
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/invoices', $request->getPath(), "path");
    $params = $request->getParams();
    SmokeTest::assertSame('cus_123', $params['customer'], "customer");
    SmokeTest::assertSame('acct_456', $params['customer_account'], "customer account");
    SmokeTest::assertSame('paid', $params['status'], "status");
    SmokeTest::assertSame(['data.payments'], $params['expand'], "expand");
});

$smoke->run("listCustomers, listRefunds, listCharges", function () use ($client, $fakeHttpClient): void {
    $client->listCustomers(email: 'jo@example.com');
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/customers', $request->getPath(), "customers path");
    SmokeTest::assertSame(['limit' => 100, 'email' => 'jo@example.com'], $request->getParams(), "customers params");

    $client->listRefunds(chargeId: 'ch_123');
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/refunds', $request->getPath(), "refunds path");
    SmokeTest::assertSame(['limit' => 100, 'charge' => 'ch_123'], $request->getParams(), "refunds params");

    $client->listCharges(paymentIntent: 'pi_123');
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/charges', $request->getPath(), "charges path");
    SmokeTest::assertSame(['limit' => 100, 'payment_intent' => 'pi_123'], $request->getParams(), "charges params");
});

$smoke->run("createCheckoutSessionForSinglePayment", function () use ($client, $fakeHttpClient): void {
    $shirt = new PriceDataForSinglePayment(Currency::GBP, 2000, new ProductData('T-shirt', taxCodeId: 'txcd_123'));
    $items = new SinglePaymentLineItemsCollection(new SinglePaymentLineItem(1, $shirt));
    $dropdown = FieldType::createDropdown(null, new DropdownOption('Red', 'red'));

    $session = $client->createCheckoutSessionForSinglePayment(
        FlowConfig::createHosted('https://example.com/done', cancelUrl: 'https://example.com/cancel'),
        $items,
        discount: Discount::createCoupon('coupon_1'),
        existingStripeCustomerOrEmail: 'jo@example.com',
        automaticTax: new AutomaticTax(true),
        customFields: new CustomFieldCollection(new CustomField('colour', 'Colour', $dropdown, true)),
        allowedPaymentMethodTypes: new PaymentMethodTypeCollection(PaymentMethodType::AFFIRM),
    );

    assert($session instanceof \Stripe\Checkout\Session, "it didn't return a session");
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/checkout/sessions', $request->getPath(), "path");
    $params = $request->getParams();
    SmokeTest::assertSame('payment', $params['mode'], "mode");
    SmokeTest::assertSame('https://example.com/cancel', $params['cancel_url'], "cancel URL");
    SmokeTest::assertSame('jo@example.com', $params['customer_email'], "email");
    SmokeTest::assertSame([['coupon' => 'coupon_1']], $params['discounts'], "discounts");
    SmokeTest::assertSame('txcd_123', $params['line_items'][0]['price_data']['product_data']['tax_code'], "tax code");
    SmokeTest::assertSame(['affirm'], $params['payment_method_types'], "payment method types");
    $customField = $params['custom_fields'][0];
    SmokeTest::assertSame('true', $customField['optional'], "custom field is optional");
    SmokeTest::assertSame([['label' => 'Red', 'value' => 'red']], $customField['dropdown']['options'], "options");
});

$smoke->run("createCheckoutSessionForSinglePayment rejects a bad email", function () use ($client): void {
    $items = new SinglePaymentLineItemsCollection(new SinglePaymentLineItem(1, 'price_123'));

    $createWithCustomerId = function () use ($client, $items): void {
        $flowConfig = FlowConfig::createHosted('https://example.com/done');
        $client->createCheckoutSessionForSinglePayment($flowConfig, $items, existingStripeCustomerOrEmail: 'cus_123');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createWithCustomerId);
});

$smoke->run("createCheckoutSessionForSubscription", function () use ($client, $fakeHttpClient): void {
    $recurring = new RecurringConfig(RecurringInterval::MONTH, 1);
    $price = new PriceDataForSubscriptionCheckoutSession(Currency::GBP, 999, 'prod_123', $recurring);
    $items = new SubscriptionCheckoutLineItemCollection(new SubscriptionCheckoutLineItem(1, $price));
    $subscriptionData = new SubscriptionData(transferData: new TransferData('acct_1', 10));

    $client->createCheckoutSessionForSubscription(
        FlowConfig::createEmbedded('https://example.com/return'),
        $items,
        subscriptionData: $subscriptionData,
        existingStripeCustomerOrEmail: new ExistingCustomer('cus_123'),
    );

    $params = $fakeHttpClient->takeLastRequest()->getParams();
    SmokeTest::assertSame('subscription', $params['mode'], "mode");
    SmokeTest::assertSame('embedded', $params['ui_mode'], "UI mode");
    SmokeTest::assertSame('cus_123', $params['customer'], "customer");
    $expectedTransfer = ['destination' => 'acct_1', 'amount_percent' => 10];
    SmokeTest::assertSame($expectedTransfer, $params['subscription_data']['transfer_data'], "transfer data");
});

$smoke->run("createSubscription", function () use ($client, $fakeHttpClient): void {
    $yearly = new RecurringConfig(RecurringInterval::YEAR, 1);
    $price = new PriceDataForSubscriptionItem(Currency::GBP, 500, 'prod_123', $yearly);

    $client->createSubscription(
        new ExistingCustomer('cus_123'),
        new SubscriptionLineItemCollection(new SubscriptionItem(1, $price)),
        Currency::GBP,
        automaticTax: new AutomaticTax(true),
        applicationFeePercent: 12.5,
        cancelAt: CancelAtEnum::MAX_PERIOD_END,
        transferData: new TransferData('acct_1', 20),
    );

    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/subscriptions', $request->getPath(), "path");
    $params = $request->getParams();
    SmokeTest::assertSame('cus_123', $params['customer'], "customer");
    SmokeTest::assertSame(['enabled' => 'true'], $params['automatic_tax'], "automatic tax");
    SmokeTest::assertSame(12.5, $params['application_fee_percent'], "application fee percent");
    SmokeTest::assertSame('max_period_end', $params['cancel_at'], "cancel at");
    SmokeTest::assertSame(['destination' => 'acct_1', 'amount_percent' => 20], $params['transfer_data'], "transfer");
});

$smoke->run("createSubscription rejects a customer ID given as a string", function () use ($client): void {
    $items = new SubscriptionLineItemCollection(new SubscriptionItem(1, 'price_123'));

    $createWithCustomerIdString = function () use ($client, $items): void {
        $client->createSubscription('cus_123', $items, Currency::GBP);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createWithCustomerIdString, "ExistingCustomer");
});

$smoke->run("listCustomerPaymentMethods", function () use ($client, $fakeHttpClient): void {
    $client->listCustomerPaymentMethods('cus_123', PaymentMethodType::CARD);
    $request = $fakeHttpClient->takeLastRequest();
    SmokeTest::assertSame('/v1/customers/cus_123/payment_methods', $request->getPath(), "path");
    SmokeTest::assertSame(['limit' => 100, 'type' => 'card'], $request->getParams(), "params");
});
