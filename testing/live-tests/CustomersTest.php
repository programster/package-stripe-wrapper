<?php

/*
 * Live tests for creating and listing customers. These are run by testing/main-live.php, which provides $smoke and
 * $fixtures. Each feature gets its own customer, so that if Stripe rejects one parameter, the others are still tested.
 * They also set $checkoutCustomerId, a plain customer that CheckoutTest.php uses.
 */

use Programster\Stripe\Collections\CustomerShippingDetailsCollection;
use Programster\Stripe\Collections\CustomerTaxIdDataCollection;
use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Collections\LocaleCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Enums\Locale;
use Programster\Stripe\Enums\ReconciliationMode;
use Programster\Stripe\Enums\TaxExempt;
use Programster\Stripe\Enums\TaxIdDataType;
use Programster\Stripe\Enums\ValidateLocation;
use Programster\Stripe\Models\Address;
use Programster\Stripe\Models\CustomerInvoiceSettings;
use Programster\Stripe\Models\CustomerShippingDetails;
use Programster\Stripe\Models\CustomerTaxConfig;
use Programster\Stripe\Models\CustomerTaxIdData;
use Programster\Stripe\Models\InvoiceCustomField;
use Programster\Stripe\Models\InvoiceRenderingOptions;
use Programster\Stripe\Models\MetadataItem;
use Programster\Stripe\StripeClient;
use Programster\Stripe\Testing\LiveFixtures;
use Programster\Stripe\Testing\SmokeTest;

/** @var \Programster\Stripe\Testing\SmokeTest $smoke */
/** @var \Programster\Stripe\Testing\LiveFixtures $fixtures */

$client = new StripeClient(STRIPE_SECRET_KEY);
$metadata = new Metadata(new MetadataItem('created_by', LiveFixtures::METADATA['created_by']));
# Unique per run, as Stripe checks some values (e.g. invoice prefixes) are unique across the account.
$runId = strtoupper(bin2hex(random_bytes(3)));
$checkoutCustomerId = '';

$smoke->run("createCustomer: address, contact details, shipping and metadata", function () use (
    $client,
    $fixtures,
    $metadata,
    $runId,
    &$checkoutCustomerId
): void {
    $address = new Address('1 High Street', 'Flat 2', 'London', 'N1 1AA', 'Greater London', 'GB');
    $shippingDetails = new CustomerShippingDetails($address, 'Jo Bloggs', '+447700900000');
    $shipping = new CustomerShippingDetailsCollection($shippingDetails);

    $customer = $client->createCustomer(
        address: $address,
        description: 'Live test customer',
        email: "jo.{$runId}@example.com",
        metadata: $metadata,
        name: 'Jo Bloggs',
        phone: '+447700900000',
        shippingDetails: $shipping,
    );

    $fixtures->trackCustomer($customer->id);
    $checkoutCustomerId = $customer->id;
    SmokeTest::assertSame('N1 1AA', $customer->address->postal_code, "address");
    SmokeTest::assertSame('Jo Bloggs', $customer->shipping->name, "shipping name");
    SmokeTest::assertSame('London', $customer->shipping->address->city, "shipping city");
    SmokeTest::assertSame(LiveFixtures::METADATA['created_by'], $customer->metadata['created_by'], "metadata");
});

$smoke->run("createCustomer: tax config", function () use ($client, $fixtures, $metadata): void {
    $taxConfig = new CustomerTaxConfig('203.0.113.1', ValidateLocation::DEFERRED);
    $customer = $client->createCustomer(metadata: $metadata, taxConfig: $taxConfig);
    $fixtures->trackCustomer($customer->id);
});

$smoke->run("createCustomer: tax ID data", function () use ($client, $fixtures, $metadata): void {
    $taxIds = new CustomerTaxIdDataCollection(new CustomerTaxIdData(TaxIdDataType::GB_VAT, 'GB123456789'));
    $customer = $client->createCustomer(metadata: $metadata, taxIdData: $taxIds);
    $fixtures->trackCustomer($customer->id);
});

$smoke->run("createCustomer: cash balance reconciliation mode", function () use ($client, $fixtures, $metadata): void {
    $customer = $client->createCustomer(metadata: $metadata, cashBalanceReconciliationMode: ReconciliationMode::MANUAL);
    $fixtures->trackCustomer($customer->id);
});

$smoke->run("createCustomer: invoice settings", function () use ($client, $fixtures, $metadata, $runId): void {
    $invoiceSettings = new CustomerInvoiceSettings(
        customFields: new InvoiceCustomFieldCollection(new InvoiceCustomField('PO number', '12345')),
        footer: 'Thank you',
        renderingOptions: new InvoiceRenderingOptions(includeTax: true),
    );

    $customer = $client->createCustomer(
        metadata: $metadata,
        invoicePrefix: "LT{$runId}",
        invoiceSettings: $invoiceSettings,
        nextInvoiceSequence: 7,
    );

    $fixtures->trackCustomer($customer->id);
    SmokeTest::assertSame('Thank you', $customer->invoice_settings->footer, "footer");
    SmokeTest::assertSame('PO number', $customer->invoice_settings->custom_fields[0]->name, "custom field");
    $amountTaxDisplay = $customer->invoice_settings->rendering_options->amount_tax_display;
    SmokeTest::assertSame('include_inclusive_tax', $amountTaxDisplay, "rendering options");
    SmokeTest::assertSame("LT{$runId}", $customer->invoice_prefix, "invoice prefix");
});

$smoke->run("createCustomer: locales, tax exemption and balance", function () use (
    $client,
    $fixtures,
    $metadata
): void {
    $customer = $client->createCustomer(
        metadata: $metadata,
        balance: -500,
        preferredLocales: new LocaleCollection(Locale::ENGLISH, Locale::FRENCH),
        taxExempt: TaxExempt::REVERSE,
    );

    $fixtures->trackCustomer($customer->id);
    SmokeTest::assertSame(['en', 'fr'], $customer->preferred_locales, "locales");
    SmokeTest::assertSame('reverse', $customer->tax_exempt, "tax exempt");
    SmokeTest::assertSame(-500, $customer->balance, "balance");
});

$smoke->run("createCustomer: individual name", function () use ($client, $fixtures, $metadata): void {
    $customer = $client->createCustomer(metadata: $metadata, individualName: 'Jo Bloggs');
    $fixtures->trackCustomer($customer->id);
});

$smoke->run("createCustomer: business name", function () use ($client, $fixtures, $metadata): void {
    $customer = $client->createCustomer(metadata: $metadata, businessName: 'Bloggs Ltd');
    $fixtures->trackCustomer($customer->id);
});

$smoke->run("createCustomer: payment method and source", function () use ($client, $fixtures, $metadata): void {
    $withPaymentMethod = $client->createCustomer(metadata: $metadata, paymentMethodId: 'pm_card_visa');
    $fixtures->trackCustomer($withPaymentMethod->id);
    $withSource = $client->createCustomer(metadata: $metadata, source: 'tok_visa');
    $fixtures->trackCustomer($withSource->id);
});

$smoke->run("listCustomers by email", function () use ($client, $runId): void {
    $customers = $client->listCustomers(email: "jo.{$runId}@example.com", limit: 10);
    SmokeTest::assertSame(1, count($customers->data), "number of customers found");
});
