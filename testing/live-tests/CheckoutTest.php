<?php

/*
 * Live tests for checkout sessions. These are run by testing/main-live.php, which provides $smoke and $fixtures, after
 * CustomersTest.php, which provides $checkoutCustomerId. Each session tests one group of options, so that if Stripe
 * rejects one, the others are still tested. Each is retrieved again with retrieveCheckoutSession(), to check what
 * Stripe stored, and is expired afterwards by the fixtures.
 */

use Programster\Stripe\Collections\AllowRedisplayFiltersCollection;
use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\PaymentMethodTypeCollection;
use Programster\Stripe\Collections\ShippingCountryCodeCollection;
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
use Programster\Stripe\Enums\ShippingCountryCode;
use Programster\Stripe\Enums\SubmitType;
use Programster\Stripe\Enums\TaxBehavior;
use Programster\Stripe\Enums\TaxIdCollection;
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
use Programster\Stripe\Models\EmbeddedFlow;
use Programster\Stripe\Models\FieldType;
use Programster\Stripe\Models\HostedFlow;
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
use Programster\Stripe\Models\SinglePaymentCheckoutCustomer;
use Programster\Stripe\Models\SinglePaymentLineItem;
use Programster\Stripe\Models\SubscriptionCheckoutCustomer;
use Programster\Stripe\Models\SubscriptionCheckoutLineItem;
use Programster\Stripe\Models\SubscriptionData;
use Programster\Stripe\Models\TaxRates;
use Programster\Stripe\Models\PaymentTransferData;
use Programster\Stripe\Models\TrialConfig;
use Programster\Stripe\Responses\CheckoutSession;
use Programster\Stripe\StripeClient;
use Programster\Stripe\Testing\LiveFixtures;
use Programster\Stripe\Testing\SmokeTest;

/** @var \Programster\Stripe\Testing\SmokeTest $smoke */
/** @var \Programster\Stripe\Testing\LiveFixtures $fixtures */
/** @var string $checkoutCustomerId - from CustomersTest.php. */

$client = new StripeClient(STRIPE_SECRET_KEY);
$metadata = new Metadata(new MetadataItem('created_by', LiveFixtures::METADATA['created_by']));
$hosted = new HostedFlow('https://example.com/success', cancelUrl: 'https://example.com/cancel');
$productData = new ProductData('Live test T-shirt', metadata: $metadata);
$shirtPrice = PriceDataForSinglePayment::createUsingInlineProduct(Currency::GBP, 2000, $productData);
$shirts = new SinglePaymentLineItemsCollection(SinglePaymentLineItem::createUsingInlinePrice($shirtPrice, 1));
$taxRateId = $fixtures->getTaxRateId();
$couponId = $fixtures->getCouponId();
$productId = $fixtures->getProductId();
$monthly = new RecurringConfig(RecurringInterval::MONTH, 1);

$subscriptionPrice = PriceDataForSubscriptionCheckoutSession::createUsingInlineProduct(
    Currency::GBP,
    999,
    $productData,
    $monthly
);

$subscriptionItem = SubscriptionCheckoutLineItem::createUsingInlinePrice($subscriptionPrice, 1);
$subscriptionItems = new SubscriptionCheckoutLineItemCollection($subscriptionItem);

# Track a session the package created, for expiring afterwards, and get what Stripe stored for it.
$retrieve = function (string $sessionId) use ($client, $fixtures): CheckoutSession {
    $fixtures->trackCheckoutSession($sessionId);
    return $client->retrieveCheckoutSession($sessionId);
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
    $price = PriceDataForSinglePayment::createUsingInlineProduct(Currency::GBP, 2000, $product);

    $item = SinglePaymentLineItem::createUsingInlinePrice(
        $price,
        1,
        new AdjustableQuantityConfig(1, 10),
        new TaxRates(false, $taxRateId)
    );

    $session = $client->createHostedCheckoutSessionForSinglePayment(
        $hosted,
        new SinglePaymentLineItemsCollection($item),
        clientReferenceId: 'order-123',
        locale: Locale::FRENCH,
        currency: Currency::GBP,
        customer: SinglePaymentCheckoutCustomer::createUsingEmail('buyer@example.com', CustomerCreation::ALWAYS),
        expiresAt: new DateTimeImmutable('+1 hour'),
        billingAddressCollection: BillingAddressCollection::REQUIRED,
        enablePhoneNumberCollection: true,
        allowedPaymentMethodTypes: new PaymentMethodTypeCollection(PaymentMethodType::CARD),
        submitType: SubmitType::PAY,
        metadata: $metadata,
    );

    $stored = $retrieve($session->getId());
    assert(str_starts_with($session->getUrl(), 'https://checkout.stripe.com/'), "not a Stripe payment page URL");
    SmokeTest::assertSame($session->getUrl(), $stored->getUrl(), "the URL Stripe stored");
    SmokeTest::assertSame('payment', $stored->getMode(), "mode");
    SmokeTest::assertSame('https://example.com/cancel', $stored->getCancelUrl(), "cancel URL");
    SmokeTest::assertSame('order-123', $stored->getClientReferenceId(), "client reference");
    SmokeTest::assertSame('fr', $stored->getStripeObject()->locale, "locale");
    SmokeTest::assertSame('buyer@example.com', $stored->getCustomerEmail(), "email");
    SmokeTest::assertSame('always', $stored->getStripeObject()->customer_creation, "customer creation");
    SmokeTest::assertSame('required', $stored->getStripeObject()->billing_address_collection, "billing address");

    SmokeTest::assertSame(
        true,
        $stored->getStripeObject()->phone_number_collection?->enabled,
        "phone number collection"
    );

    SmokeTest::assertSame('pay', $stored->getStripeObject()->submit_type, "submit type");
});

$smoke->run("single payment: coupon discount", function () use ($client, $retrieve, $hosted, $shirts, $couponId): void {
    $discount = Discount::createUsingCouponId($couponId);
    $session = $client->createHostedCheckoutSessionForSinglePayment($hosted, $shirts, discount: $discount);
    $stored = $retrieve($session->getId());
    $discounts = $stored->getStripeObject()->discounts;
    assert(isset($discounts[0]), "the session has no discounts");
    SmokeTest::assertSame($couponId, $discounts[0]->coupon, "coupon");
});

$smoke->run("single payment: promotion codes and recovery", function () use (
    $client,
    $retrieve,
    $hosted,
    $shirts
): void {
    $session = $client->createHostedCheckoutSessionForSinglePayment(
        $hosted,
        $shirts,
        allowPromoCodes: true,
        afterExpiration: AfterExpiration::createEnabled(true),
    );

    $stored = $retrieve($session->getId());
    SmokeTest::assertSame(true, $stored->getStripeObject()->after_expiration?->recovery?->enabled, "recovery");

    SmokeTest::assertSame(
        true,
        $stored->getStripeObject()->after_expiration?->recovery?->allow_promotion_codes,
        "promotion codes"
    );
});

$smoke->run("single payment: payment intent data", function () use (
    $client,
    $retrieve,
    $hosted,
    $shirts,
    $metadata
): void {
    $shipping = new ShippingConfig('Jo Bloggs', new Address('1 High Street', city: 'London', country: CountryCode::GB));

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

    $session = $client->createHostedCheckoutSessionForSinglePayment($hosted, $shirts, $paymentIntentData);
    $retrieve($session->getId());
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

    $creation = InvoiceCreation::createEnabled($invoiceData);
    $session = $client->createHostedCheckoutSessionForSinglePayment($hosted, $shirts, invoiceCreation: $creation);
    $stored = $retrieve($session->getId());
    SmokeTest::assertSame(true, $stored->getStripeObject()->invoice_creation?->enabled, "invoice creation");
    SmokeTest::assertSame('Thank you', $stored->getStripeObject()->invoice_creation?->invoice_data->footer, "footer");
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
        new CustomField('membership', 'Membership number', FieldType::createNumeric('', 4, 10), true)
    );

    $customText = new CustomTextOptions('Thanks for your order', 'We ship worldwide', 'Pay securely');

    $session = $client->createHostedCheckoutSessionForSinglePayment(
        $hosted,
        $shirts,
        customTextOptions: $customText,
        shippingAddressCollection: new ShippingCountryCodeCollection(ShippingCountryCode::GB, ShippingCountryCode::US),
        customFields: $customFields,
    );

    $stored = $retrieve($session->getId());
    SmokeTest::assertSame(3, count($stored->getStripeObject()->custom_fields), "number of custom fields");
    SmokeTest::assertSame(false, $stored->getStripeObject()->custom_fields[0]->optional, "colour is required");

    SmokeTest::assertSame(
        'red',
        SmokeTest::getNested($stored->getStripeObject()->toArray(), 'custom_fields', 0, 'dropdown', 'default_value'),
        "dropdown default"
    );

    SmokeTest::assertSame(true, $stored->getStripeObject()->custom_fields[1]->optional, "engraving is optional");

    SmokeTest::assertSame(
        10,
        SmokeTest::getNested($stored->getStripeObject()->toArray(), 'custom_fields', 2, 'numeric', 'maximum_length'),
        "numeric maximum length"
    );

    SmokeTest::assertSame('Pay securely', $stored->getStripeObject()->custom_text->submit?->message, "submit text");

    SmokeTest::assertSame(
        ['GB', 'US'],
        $stored->getStripeObject()->shipping_address_collection?->allowed_countries,
        "countries"
    );
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

    $session = $client->createHostedCheckoutSessionForSinglePayment(
        $hosted,
        $shirts,
        customer: SinglePaymentCheckoutCustomer::createUsingCustomerId($checkoutCustomerId, $customerUpdate),
        taxIdCollection: TaxIdCollection::REQUIRED_WHERE_SUPPORTED,
        savedPaymentMethodOptions: $savedPaymentMethods,
    );

    $stored = $retrieve($session->getId());
    SmokeTest::assertSame($checkoutCustomerId, $stored->getCustomerId(), "customer");

    SmokeTest::assertSame(
        'enabled',
        $stored->getStripeObject()->saved_payment_method_options?->payment_method_save,
        "save"
    );

    $taxIdRequired = $stored->getStripeObject()->tax_id_collection?->required;
    SmokeTest::assertSame('if_supported', $taxIdRequired, "tax ID collection");
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
        $session = $client->createHostedCheckoutSessionForSinglePayment($hosted, $shirts, consentConfig: $consent);
        $stored = $retrieve($session->getId());

        SmokeTest::assertSame(
            'required',
            $stored->getStripeObject()->consent_collection?->terms_of_service,
            "terms of service"
        );
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
        $session = $client->createHostedCheckoutSessionForSinglePayment($hosted, $shirts, automaticTax: $automaticTax);
        $stored = $retrieve($session->getId());
        SmokeTest::assertSame(true, $stored->getStripeObject()->automatic_tax->enabled, "automatic tax");
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
        $transferData = new PaymentTransferData(STRIPE_CONNECTED_ACCOUNT_ID, 500);
        $paymentIntentData = new PaymentIntentData(transferData: $transferData);
        $session = $client->createHostedCheckoutSessionForSinglePayment($hosted, $shirts, $paymentIntentData);
        $retrieve($session->getId());
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
        trialConfig: TrialConfig::createUsingNumDays(14, EndTrialBehaviorIfMissingPaymentMethod::PAUSE),
    );

    $session = $client->createHostedCheckoutSessionForSubscription(
        $hosted,
        $subscriptionItems,
        $subscriptionData,
        customer: SubscriptionCheckoutCustomer::createUsingEmail('subscriber@example.com'),
        metadata: $metadata,
    );

    $stored = $retrieve($session->getId());
    SmokeTest::assertSame('subscription', $stored->getMode(), "mode");
});

$smoke->run("subscription: billing cycle anchor and tax-inclusive price", function () use (
    $client,
    $retrieve,
    $hosted,
    $monthly,
    $productId
): void {
    $inclusivePrice = PriceDataForSubscriptionCheckoutSession::createUsingExistingProduct(
        Currency::GBP,
        1200,
        $productId,
        $monthly,
        TaxBehavior::INCLUSIVE
    );

    $inclusiveItem = SubscriptionCheckoutLineItem::createUsingInlinePrice($inclusivePrice, 1);
    $items = new SubscriptionCheckoutLineItemCollection($inclusiveItem);
    $nextMonth = new DateTimeImmutable('first day of next month');
    $none = ProrationBehavior::NONE;
    $subscriptionData = new SubscriptionData(billingCycleAnchor: $nextMonth, prorationBehavior: $none);
    $session = $client->createHostedCheckoutSessionForSubscription($hosted, $items, $subscriptionData);
    $retrieve($session->getId());
});

$smoke->run("subscription: embedded flow", function () use ($client, $retrieve, $subscriptionItems): void {
    $embedded = new EmbeddedFlow('https://example.com/return', RedirectOnCompletion::IF_REQUIRED);
    $session = $client->createEmbeddedCheckoutSessionForSubscription($embedded, $subscriptionItems);
    $stored = $retrieve($session->getId());
    SmokeTest::assertSame('if_required', $stored->getStripeObject()->redirect_on_completion, "redirect on completion");
    SmokeTest::assertSame('embedded', $stored->getUiMode(), "UI mode");
    assert($session->getClientSecret() !== '', "the client secret for embedding is empty");
});

$smoke->run("single payment: embedded flow", function () use ($client, $retrieve, $shirts): void {
    $embedded = new EmbeddedFlow('https://example.com/return');
    $session = $client->createEmbeddedCheckoutSessionForSinglePayment($embedded, $shirts);
    $stored = $retrieve($session->getId());
    SmokeTest::assertSame('embedded', $stored->getUiMode(), "UI mode");
    SmokeTest::assertSame('always', $stored->getStripeObject()->redirect_on_completion, "redirect on completion");
    assert($session->getClientSecret() !== '', "the client secret for embedding is empty");
});
