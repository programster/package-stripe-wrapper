<?php

/*
 * Live tests for checkout sessions. These are run by testing/main-live.php, which provides $smoke and $fixtures, after
 * CustomersTest.php, which provides $checkoutCustomerId. Each session tests one group of options, so that if Stripe
 * rejects one, the others are still tested. Each is retrieved again with retrieveCheckoutSession(), to check what
 * Stripe stored, and is expired afterwards by the fixtures.
 */

use Programster\Stripe\Collections\AllowRedisplayFiltersCollection;
use Programster\Stripe\Collections\CountryCodeCollection;
use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\PaymentMethodTypeCollection;
use Programster\Stripe\Collections\SinglePaymentLineItemsCollection;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Collections\SubscriptionCheckoutLineItemCollection;
use Programster\Stripe\Enums\AllowRedisplayFilters;
use Programster\Stripe\Enums\BillingAddressCollection;
use Programster\Stripe\Enums\CaptureMethod;
use Programster\Stripe\Enums\CountryCode;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\CustomerCreation;
use Programster\Stripe\Enums\EndTrialBehaviorIfMissingPaymentMethod;
use Programster\Stripe\Enums\Locale;
use Programster\Stripe\Enums\PaymentIntentSetupFutureUsage;
use Programster\Stripe\Enums\PaymentMethodType;
use Programster\Stripe\Enums\ProrationBehavior;
use Programster\Stripe\Enums\ProrationDiscountConfig;
use Programster\Stripe\Enums\RecurringInterval;
use Programster\Stripe\Enums\RedirectOnCompletion;
use Programster\Stripe\Enums\SubmitType;
use Programster\Stripe\Enums\TaxBehavior;
use Programster\Stripe\Enums\Updateable;
use Programster\Stripe\Models\AccountReference;
use Programster\Stripe\Models\Address;
use Programster\Stripe\Models\AdjustableQuantityConfig;
use Programster\Stripe\Models\AfterExpiration;
use Programster\Stripe\Models\AutomaticTax;
use Programster\Stripe\Models\BillingMode;
use Programster\Stripe\Models\ConsentConfig;
use Programster\Stripe\Models\CustomerUpdateConfig;
use Programster\Stripe\Models\CustomField;
use Programster\Stripe\Models\CustomTextOptions;
use Programster\Stripe\Models\Discount;
use Programster\Stripe\Models\DropdownOption;
use Programster\Stripe\Models\ExistingCustomer;
use Programster\Stripe\Models\FieldType;
use Programster\Stripe\Models\FlowConfig;
use Programster\Stripe\Models\InvoiceCreation;
use Programster\Stripe\Models\InvoiceCustomField;
use Programster\Stripe\Models\InvoiceData;
use Programster\Stripe\Models\InvoiceRenderingOptions;
use Programster\Stripe\Models\InvoiceSettings;
use Programster\Stripe\Models\MetadataItem;
use Programster\Stripe\Models\PaymentIntentData;
use Programster\Stripe\Models\PriceDataForSinglePayment;
use Programster\Stripe\Models\PriceDataForSubscriptionCheckoutSession;
use Programster\Stripe\Models\ProductData;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SavedPaymentMethodOptions;
use Programster\Stripe\Models\ShippingConfig;
use Programster\Stripe\Models\SinglePaymentLineItem;
use Programster\Stripe\Models\SubscriptionCheckoutLineItem;
use Programster\Stripe\Models\SubscriptionData;
use Programster\Stripe\Models\TaxIdCollectionConfig;
use Programster\Stripe\Models\TaxRates;
use Programster\Stripe\Models\TransferData;
use Programster\Stripe\Models\TrialConfig;
use Programster\Stripe\StripeClient;
use Programster\Stripe\Testing\LiveFixtures;
use Programster\Stripe\Testing\SmokeTest;

/** @var \Programster\Stripe\Testing\SmokeTest $smoke */
/** @var \Programster\Stripe\Testing\LiveFixtures $fixtures */
/** @var string $checkoutCustomerId - from CustomersTest.php. */

$client = new StripeClient(STRIPE_SECRET_KEY);
$metadata = new Metadata(new MetadataItem('created_by', LiveFixtures::METADATA['created_by']));
$hosted = FlowConfig::createHosted('https://example.com/success', cancelUrl: 'https://example.com/cancel');
$productData = new ProductData('Live test T-shirt', metadata: $metadata);
$shirtPrice = new PriceDataForSinglePayment(Currency::GBP, 2000, $productData);
$shirts = new SinglePaymentLineItemsCollection(new SinglePaymentLineItem(1, $shirtPrice));
$taxRateId = $fixtures->getTaxRateId();
$couponId = $fixtures->getCouponId();
$productId = $fixtures->getProductId();
$monthly = new RecurringConfig(RecurringInterval::MONTH, 1);
$subscriptionPrice = new PriceDataForSubscriptionCheckoutSession(Currency::GBP, 999, $productData, $monthly);
$subscriptionItem = new SubscriptionCheckoutLineItem(1, $subscriptionPrice);
$subscriptionItems = new SubscriptionCheckoutLineItemCollection($subscriptionItem);

# Track a session the package created, for expiring afterwards, and get what Stripe stored for it.
$retrieve = function (\Stripe\Checkout\Session $session) use ($client, $fixtures): \Stripe\Checkout\Session {
    $fixtures->trackCheckoutSession($session->id);
    return $client->retrieveCheckoutSession($session->id);
};

$smoke->run("single payment: hosted flow and basic options", function () use (
    $client,
    $retrieve,
    $hosted,
    $metadata,
    $taxRateId
): void {
    $images = new StringCollection('https://example.com/shirt.png');
    $product = new ProductData('Live test shirt', 'A shirt', $images, $metadata, 'txcd_99999999');
    $price = new PriceDataForSinglePayment(Currency::GBP, 2000, $product);

    $item = new SinglePaymentLineItem(
        1,
        $price,
        new AdjustableQuantityConfig(1, 10),
        new TaxRates(false, $taxRateId)
    );

    $session = $client->createCheckoutSessionForSinglePayment(
        $hosted,
        new SinglePaymentLineItemsCollection($item),
        clientReferenceId: 'order-123',
        locale: Locale::FRENCH,
        currency: Currency::GBP,
        existingStripeCustomerOrEmail: 'buyer@example.com',
        customerCreation: CustomerCreation::ALWAYS,
        expiresAt: time() + 60 * 60,
        billingAddressCollection: BillingAddressCollection::REQUIRED,
        enablePhoneNumberCollection: true,
        allowedPaymentMethodTypes: new PaymentMethodTypeCollection(PaymentMethodType::CARD),
        submitType: SubmitType::PAY,
        metadata: $metadata,
    );

    $stored = $retrieve($session);
    SmokeTest::assertSame('payment', $stored->mode, "mode");
    SmokeTest::assertSame('https://example.com/cancel', $stored->cancel_url, "cancel URL");
    SmokeTest::assertSame('order-123', $stored->client_reference_id, "client reference");
    SmokeTest::assertSame('fr', $stored->locale, "locale");
    SmokeTest::assertSame('buyer@example.com', $stored->customer_email, "email");
    SmokeTest::assertSame('always', $stored->customer_creation, "customer creation");
    SmokeTest::assertSame('required', $stored->billing_address_collection, "billing address");
    SmokeTest::assertSame(true, $stored->phone_number_collection->enabled, "phone number collection");
    SmokeTest::assertSame('pay', $stored->submit_type, "submit type");
});

$smoke->run("single payment: coupon discount", function () use ($client, $retrieve, $hosted, $shirts, $couponId): void {
    $discount = Discount::createCoupon($couponId);
    $session = $client->createCheckoutSessionForSinglePayment($hosted, $shirts, discount: $discount);
    $stored = $retrieve($session);
    SmokeTest::assertSame($couponId, $stored->discounts[0]->coupon, "coupon");
});

$smoke->run("single payment: promotion codes and recovery", function () use (
    $client,
    $retrieve,
    $hosted,
    $shirts
): void {
    $session = $client->createCheckoutSessionForSinglePayment(
        $hosted,
        $shirts,
        allowPromoCodes: true,
        afterExpiration: AfterExpiration::createEnabled(true),
    );

    $stored = $retrieve($session);
    SmokeTest::assertSame(true, $stored->after_expiration->recovery->enabled, "recovery");
    SmokeTest::assertSame(true, $stored->after_expiration->recovery->allow_promotion_codes, "promotion codes");
});

$smoke->run("single payment: payment intent data", function () use (
    $client,
    $retrieve,
    $hosted,
    $shirts,
    $metadata
): void {
    $shipping = new ShippingConfig('Jo Bloggs', new Address('1 High Street', city: 'London', country: 'GB'));

    $paymentIntentData = new PaymentIntentData(
        description: 'Live test payment',
        captureMethod: CaptureMethod::MANUAL,
        metadata: $metadata,
        receiptEmail: 'receipt@example.com',
        setupFutureUsage: PaymentIntentSetupFutureUsage::ON_SESSION,
        shippingConfig: $shipping,
        statementDescriptorSuffix: 'LIVETEST',
        transferGroup: 'live-test-group',
    );

    $session = $client->createCheckoutSessionForSinglePayment($hosted, $shirts, $paymentIntentData);
    $retrieve($session);
});

$smoke->run("single payment: post-purchase invoice", function () use (
    $client,
    $retrieve,
    $hosted,
    $shirts,
    $metadata
): void {
    $invoiceData = new InvoiceData(
        customFields: new InvoiceCustomFieldCollection(new InvoiceCustomField('PO number', '12345')),
        description: 'Live test invoice',
        footer: 'Thank you',
        issuer: AccountReference::createSelf(),
        metadata: $metadata,
        renderingOptions: new InvoiceRenderingOptions(includeTax: false),
    );

    $invoiceCreation = InvoiceCreation::createEnabled($invoiceData);
    $session = $client->createCheckoutSessionForSinglePayment($hosted, $shirts, invoiceCreation: $invoiceCreation);
    $stored = $retrieve($session);
    SmokeTest::assertSame(true, $stored->invoice_creation->enabled, "invoice creation");
    SmokeTest::assertSame('Thank you', $stored->invoice_creation->invoice_data->footer, "footer");
});

$smoke->run("single payment: custom fields, custom text and shipping", function () use (
    $client,
    $retrieve,
    $hosted,
    $shirts
): void {
    $dropdown = FieldType::createDropdown(new DropdownOption('Red', 'red'), new DropdownOption('Blue', 'blue'));

    $customFields = new CustomFieldCollection(
        new CustomField('colour', 'Colour', $dropdown, false),
        new CustomField('engraving', 'Engraving', FieldType::createText('', 1, 30), true),
        new CustomField('membership', 'Membership number', FieldType::createNumeric('', 10, 4), true)
    );

    $customText = new CustomTextOptions('Thanks for your order', 'We ship worldwide', 'Pay securely');

    $session = $client->createCheckoutSessionForSinglePayment(
        $hosted,
        $shirts,
        customTextOptions: $customText,
        shippingAddressCollection: new CountryCodeCollection(CountryCode::GB, CountryCode::US),
        customFields: $customFields,
    );

    $stored = $retrieve($session);
    SmokeTest::assertSame(3, count($stored->custom_fields), "number of custom fields");
    SmokeTest::assertSame(false, $stored->custom_fields[0]->optional, "colour is required");
    SmokeTest::assertSame('red', $stored->custom_fields[0]->dropdown->default_value, "dropdown default");
    SmokeTest::assertSame(true, $stored->custom_fields[1]->optional, "engraving is optional");
    SmokeTest::assertSame(10, $stored->custom_fields[2]->numeric->maximum_length, "numeric maximum length");
    SmokeTest::assertSame('Pay securely', $stored->custom_text->submit->message, "submit text");
    SmokeTest::assertSame(['GB', 'US'], $stored->shipping_address_collection->allowed_countries, "countries");
});

$smoke->run("single payment: existing customer, saved payment methods, tax IDs", function () use (
    $client,
    $retrieve,
    $hosted,
    $shirts,
    &$checkoutCustomerId
): void {
    $savedPaymentMethods = new SavedPaymentMethodOptions(
        true,
        true,
        new AllowRedisplayFiltersCollection(AllowRedisplayFilters::ALWAYS)
    );

    $customerUpdate = new CustomerUpdateConfig(Updateable::AUTO, Updateable::AUTO, Updateable::AUTO);

    $session = $client->createCheckoutSessionForSinglePayment(
        $hosted,
        $shirts,
        existingStripeCustomerOrEmail: new ExistingCustomer($checkoutCustomerId, $customerUpdate),
        taxIdCollection: new TaxIdCollectionConfig(true, true),
        savedPaymentMethodOptions: $savedPaymentMethods,
    );

    $stored = $retrieve($session);
    SmokeTest::assertSame($checkoutCustomerId, $stored->customer, "customer");
    SmokeTest::assertSame('enabled', $stored->saved_payment_method_options->payment_method_save, "save");
    SmokeTest::assertSame('if_supported', $stored->tax_id_collection->required, "tax ID collection");
});

if (STRIPE_TERMS_OF_SERVICE_URL_IS_SET)
{
    $smoke->run("single payment: terms of service consent", function () use (
        $client,
        $retrieve,
        $hosted,
        $shirts
    ): void {
        $consent = new ConsentConfig(true, hidePaymentMethodReuseAgreement: true);
        $session = $client->createCheckoutSessionForSinglePayment($hosted, $shirts, consentConfig: $consent);
        $stored = $retrieve($session);
        SmokeTest::assertSame('required', $stored->consent_collection->terms_of_service, "terms of service");
    });
}
else
{
    $reason = "set STRIPE_TERMS_OF_SERVICE_URL_IS_SET in settings.php";
    $smoke->skip("single payment: terms of service consent", $reason);
}

if (STRIPE_TAX_IS_SET_UP)
{
    $smoke->run("single payment: automatic tax", function () use ($client, $retrieve, $hosted, $shirts): void {
        $automaticTax = new AutomaticTax(true);
        $session = $client->createCheckoutSessionForSinglePayment($hosted, $shirts, automaticTax: $automaticTax);
        $stored = $retrieve($session);
        SmokeTest::assertSame(true, $stored->automatic_tax->enabled, "automatic tax");
    });
}
else
{
    $smoke->skip("single payment: automatic tax", "set STRIPE_TAX_IS_SET_UP in settings.php");
}

if (STRIPE_CONNECTED_ACCOUNT_ID !== '')
{
    $smoke->run("single payment: Stripe Connect transfer", function () use (
        $client,
        $retrieve,
        $hosted,
        $shirts
    ): void {
        $paymentIntentData = new PaymentIntentData(transferData: new TransferData(STRIPE_CONNECTED_ACCOUNT_ID, 500));
        $session = $client->createCheckoutSessionForSinglePayment($hosted, $shirts, $paymentIntentData);
        $retrieve($session);
    });
}
else
{
    $smoke->skip("single payment: Stripe Connect transfer", "set STRIPE_CONNECTED_ACCOUNT_ID in settings.php");
}

$smoke->run("subscription: hosted flow with subscription data", function () use (
    $client,
    $retrieve,
    $hosted,
    $subscriptionItems,
    $metadata,
    $taxRateId
): void {
    $subscriptionData = new SubscriptionData(
        billingMode: BillingMode::createFlexible(ProrationDiscountConfig::INCLUDED),
        defaultTaxRates: new StringCollection($taxRateId),
        description: 'Live test subscription',
        invoiceSettings: new InvoiceSettings(AccountReference::createSelf()),
        metadata: $metadata,
        trialConfig: TrialConfig::createForNumDays(14, EndTrialBehaviorIfMissingPaymentMethod::PAUSE),
    );

    $session = $client->createCheckoutSessionForSubscription(
        $hosted,
        $subscriptionItems,
        $subscriptionData,
        existingStripeCustomerOrEmail: 'subscriber@example.com',
        metadata: $metadata,
    );

    $stored = $retrieve($session);
    SmokeTest::assertSame('subscription', $stored->mode, "mode");
});

$smoke->run("subscription: billing cycle anchor and tax-inclusive price", function () use (
    $client,
    $retrieve,
    $hosted,
    $monthly,
    $productId
): void {
    $inclusivePrice = new PriceDataForSubscriptionCheckoutSession(
        Currency::GBP,
        1200,
        $productId,
        $monthly,
        TaxBehavior::INCLUSIVE
    );

    $items = new SubscriptionCheckoutLineItemCollection(new SubscriptionCheckoutLineItem(1, $inclusivePrice));
    $nextMonth = strtotime('first day of next month');
    $none = ProrationBehavior::NONE;
    $subscriptionData = new SubscriptionData(billingCycleAnchor: $nextMonth, prorationBehavior: $none);
    $session = $client->createCheckoutSessionForSubscription($hosted, $items, $subscriptionData);
    $retrieve($session);
});

$smoke->run("subscription: embedded flow", function () use ($client, $retrieve, $subscriptionItems): void {
    $embedded = FlowConfig::createEmbedded('https://example.com/return', RedirectOnCompletion::IF_REQUIRED);
    $session = $client->createCheckoutSessionForSubscription($embedded, $subscriptionItems);
    $stored = $retrieve($session);
    SmokeTest::assertSame('if_required', $stored->redirect_on_completion, "redirect on completion");
    assert(is_string($stored->client_secret) && $stored->client_secret !== '', "no client secret for embedding");
});
