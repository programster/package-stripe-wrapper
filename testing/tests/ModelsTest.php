<?php

/*
 * Smoke tests for the models, enums, collections and exceptions. These are run by testing/main.php, which provides
 * $smoke. Each test builds something, and checks what it would send to Stripe, or that a mistake is rejected where
 * it was made.
 */

use Programster\Stripe\Collections\AllowRedisplayFiltersCollection;
use Programster\Stripe\Collections\CountryCodeCollection;
use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\PaymentMethodTypeCollection;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Collections\SubscriptionInvoiceItemCollection;
use Programster\Stripe\Collections\SubscriptionLineItemCollection;
use Programster\Stripe\Enums\AllowRedisplayFilters;
use Programster\Stripe\Enums\BillingModeType;
use Programster\Stripe\Enums\CountryCode;
use Programster\Stripe\Enums\Currency;
use Programster\Stripe\Enums\EndTrialBehaviorIfMissingPaymentMethod;
use Programster\Stripe\Enums\PaymentIntentSetupFutureUsage;
use Programster\Stripe\Enums\PaymentMethodType;
use Programster\Stripe\Enums\ProrationBehavior;
use Programster\Stripe\Enums\ProrationDiscountConfig;
use Programster\Stripe\Enums\RecurringInterval;
use Programster\Stripe\Enums\SubscriptionStatus;
use Programster\Stripe\Enums\Updateable;
use Programster\Stripe\Exceptions\ExceptionDropdownOptionsMustBeUnique;
use Programster\Stripe\Exceptions\ExceptionDuplicateKey;
use Programster\Stripe\Exceptions\ExceptionInvalidDropdownValue;
use Programster\Stripe\Exceptions\ExceptionInvalidKeyName;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Exceptions\ExceptionMissingRequiredParameter;
use Programster\Stripe\Exceptions\ExceptionValueTooLong;
use Programster\Stripe\Models\AccountReference;
use Programster\Stripe\Models\AdjustableQuantityConfig;
use Programster\Stripe\Models\AfterExpiration;
use Programster\Stripe\Models\BillingCycleAnchorConfig;
use Programster\Stripe\Models\BillingMode;
use Programster\Stripe\Models\BillingThresholds;
use Programster\Stripe\Models\ConsentConfig;
use Programster\Stripe\Models\CustomerUpdateConfig;
use Programster\Stripe\Models\CustomTextOptions;
use Programster\Stripe\Models\DropdownOption;
use Programster\Stripe\Models\ExistingCustomer;
use Programster\Stripe\Models\FieldType;
use Programster\Stripe\Models\InvoiceCreation;
use Programster\Stripe\Models\InvoiceCustomField;
use Programster\Stripe\Models\InvoiceData;
use Programster\Stripe\Models\InvoiceItemPeriod;
use Programster\Stripe\Models\InvoiceRenderingOptions;
use Programster\Stripe\Models\InvoiceSettings;
use Programster\Stripe\Models\MetadataItem;
use Programster\Stripe\Models\PaymentIntentData;
use Programster\Stripe\Models\PaymentSettings;
use Programster\Stripe\Models\PeriodEnd;
use Programster\Stripe\Models\PeriodStart;
use Programster\Stripe\Models\PriceDataForInvoiceItem;
use Programster\Stripe\Models\PriceDataForSinglePayment;
use Programster\Stripe\Models\ProductData;
use Programster\Stripe\Models\RecurringConfig;
use Programster\Stripe\Models\SavedPaymentMethodOptions;
use Programster\Stripe\Models\ShippingConfig;
use Programster\Stripe\Models\Address;
use Programster\Stripe\Models\SubscriptionInvoiceItem;
use Programster\Stripe\Models\SubscriptionItem;
use Programster\Stripe\Models\SubscriptionData;
use Programster\Stripe\Models\SubscriptionItemDiscount;
use Programster\Stripe\Models\TaxIdCollectionConfig;
use Programster\Stripe\Models\TaxRates;
use Programster\Stripe\Models\TimePeriod;
use Programster\Stripe\Models\PaymentTransferData;
use Programster\Stripe\Models\SubscriptionTransferData;
use Programster\Stripe\Models\TrialConfig;
use Programster\Stripe\Testing\SmokeTest;

/** @var \Programster\Stripe\Testing\SmokeTest $smoke */

$smoke->run("enum values match Stripe's", function (): void {
    SmokeTest::assertSame("classic", BillingModeType::CLASSIC->value, "classic billing mode");
    SmokeTest::assertSame("on_session", PaymentIntentSetupFutureUsage::ON_SESSION->value, "on session");
    SmokeTest::assertSame("affirm", PaymentMethodType::AFFIRM->value, "affirm");
    SmokeTest::assertSame("canceled", SubscriptionStatus::CANCELLED->value, "canceled");
});

$smoke->run("AccountReference", function (): void {
    SmokeTest::assertSame(['type' => 'self'], AccountReference::createSelf()->toArray(), "self");
    $related = AccountReference::createRelatedAccount('acct_1')->toArray();
    SmokeTest::assertSame(['type' => 'account', 'account' => 'acct_1'], $related, "related");
});

$smoke->run("AfterExpiration", function (): void {
    $enabled = ['recovery' => ['enabled' => true, 'allow_promotion_codes' => true]];
    SmokeTest::assertSame($enabled, AfterExpiration::createEnabled(true)->toArray(), "enabled");
    $disabled = AfterExpiration::createDisabled()->toArray();
    SmokeTest::assertSame(['recovery' => ['enabled' => false]], $disabled, "disabled");
});

$smoke->run("BillingCycleAnchorConfig with only a day of the month", function (): void {
    SmokeTest::assertSame(['day_of_month' => 15], (new BillingCycleAnchorConfig(15))->toArray(), "day only");

    $createWithBadMonth = function (): void {
        new BillingCycleAnchorConfig(1, 13);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createWithBadMonth, "Month");
});

$smoke->run("BillingMode", function (): void {
    SmokeTest::assertSame(['type' => 'classic'], BillingMode::createClassic()->toArray(), "classic");
    $flexible = BillingMode::createFlexible(ProrationDiscountConfig::ITEMIZED)->toArray();
    $expected = ['type' => 'flexible', 'flexible' => ['proration_discounts' => 'itemized']];
    SmokeTest::assertSame($expected, $flexible, "flexible");
});

$smoke->run("BillingThresholds", function (): void {
    SmokeTest::assertSame(['usage_gte' => 10], (new BillingThresholds(10))->toStripeForm(), "set");
    SmokeTest::assertSame("", (new BillingThresholds(null))->toStripeForm(), "removed");
});

$smoke->run("ConsentConfig, CustomerUpdateConfig, TaxIdCollectionConfig", function (): void {
    $consent = (new ConsentConfig(true, hidePromotionsConsent: true))->toArray();
    SmokeTest::assertSame(['terms_of_service' => 'required', 'promotions' => 'none'], $consent, "consent");
    $update = (new CustomerUpdateConfig(address: Updateable::AUTO))->toArray();
    SmokeTest::assertSame(['address' => 'auto', 'name' => 'never', 'shipping' => 'never'], $update, "update");
    $taxId = (new TaxIdCollectionConfig(true, true))->toArray();
    SmokeTest::assertSame(['enabled' => true, 'required' => 'if_supported'], $taxId, "tax ID collection");
});

$smoke->run("CustomTextOptions sends each message under its own name", function (): void {
    $options = (new CustomTextOptions(submit: 'Pay now', termsOfServiceAcceptance: 'I agree'))->toArray();
    $expected = ['submit' => ['message' => 'Pay now'], 'terms_of_service_acceptance' => ['message' => 'I agree']];
    SmokeTest::assertSame($expected, $options, "options");

    $createEmpty = function (): void {
        new CustomTextOptions();
    };

    SmokeTest::expectException(ExceptionMissingRequiredParameter::class, $createEmpty);
});

$smoke->run("DropdownOption checks its value", function (): void {
    SmokeTest::assertSame(['label' => 'Red', 'value' => 'red1'], (new DropdownOption('Red', 'red1'))->toArray(), "ok");

    $createWithSpace = function (): void {
        new DropdownOption('Dark red', 'dark red');
    };

    SmokeTest::expectException(ExceptionInvalidDropdownValue::class, $createWithSpace);

    $createWithLongValue = function (): void {
        new DropdownOption('Red', str_repeat('a', 101));
    };

    SmokeTest::expectException(ExceptionValueTooLong::class, $createWithLongValue, "value is too long");
});

$smoke->run("FieldType", function (): void {
    $red = new DropdownOption('Red', 'red');
    $blue = new DropdownOption('Blue', 'blue');
    $dropdown = FieldType::createDropdown($red, $blue)->toArray();
    SmokeTest::assertSame('red', $dropdown['dropdown']['default_value'], "default");
    SmokeTest::assertSame(2, count($dropdown['dropdown']['options']), "options");
    $noDefault = FieldType::createDropdown(null, $blue)->toArray();
    assert(!isset($noDefault['dropdown']['default_value']), "an absent default was sent");
    $text = FieldType::createText('', 1, 20)->toArray();
    $expectedText = ['minimum_length' => 1, 'maximum_length' => 20];
    SmokeTest::assertSame($expectedText, $text['text'], "an empty default isn't sent");
    $textWithDefault = FieldType::createText('Hi', 1, 20)->toArray();
    SmokeTest::assertSame('Hi', $textWithDefault['text']['default_value'], "default");
    $numeric = FieldType::createNumeric('', 1, 20)->toArray();
    SmokeTest::assertSame(20, $numeric['numeric']['maximum_length'], "numeric");

    $createWithDuplicateDefault = function () use ($red): void {
        FieldType::createDropdown($red, new DropdownOption('Also red', 'red'));
    };

    SmokeTest::expectException(ExceptionDropdownOptionsMustBeUnique::class, $createWithDuplicateDefault);

    $createWithSwappedLengths = function (): void {
        FieldType::createText('', 20, 1);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createWithSwappedLengths, "minimum");
});

$smoke->run("InvoiceCreation, InvoiceData, InvoiceRenderingOptions", function (): void {
    $metadata = new Metadata(new MetadataItem('ref', '1'));
    $rendering = new InvoiceRenderingOptions(templateId: 'inrtem_1');
    $customFields = new InvoiceCustomFieldCollection(new InvoiceCustomField('PO', '123'));
    $data = new InvoiceData(customFields: $customFields, metadata: $metadata, renderingOptions: $rendering);
    $creation = InvoiceCreation::createEnabled($data)->toArray();
    SmokeTest::assertSame(['ref' => '1'], $creation['invoice_data']['metadata'], "metadata");
    SmokeTest::assertSame(['template_id' => 'inrtem_1'], $creation['invoice_data']['rendering_options'], "rendering");
    SmokeTest::assertSame([['name' => 'PO', 'value' => '123']], $creation['invoice_data']['custom_fields'], "fields");
    SmokeTest::assertSame(['enabled' => false], InvoiceCreation::createDisabled()->toArray(), "disabled");
    $issuer = (new InvoiceSettings(AccountReference::createSelf()))->toArray();
    SmokeTest::assertSame(['issuer' => ['type' => 'self']], $issuer, "invoice settings");
});

$smoke->run("InvoiceItemPeriod puts the start and end the right way round", function (): void {
    $period = (new InvoiceItemPeriod(PeriodStart::createNow(), PeriodEnd::createTimestamp(1000)))->toArray();
    $expected = ['end' => ['type' => 'timestamp', 'timestamp' => 1000], 'start' => ['type' => 'now']];
    SmokeTest::assertSame($expected, $period, "period");
    $maxStart = PeriodStart::createMaxItemPeriodStart()->toArray();
    SmokeTest::assertSame(['type' => 'max_item_period_start'], $maxStart, "max start");
    $minEnd = PeriodEnd::createMinItemPeriodEnd()->toArray();
    SmokeTest::assertSame(['type' => 'min_item_period_end'], $minEnd, "min end");
});

$smoke->run("MetadataItem and Metadata check their keys", function (): void {
    $createWithBracket = function (): void {
        new MetadataItem('a[b]', 'x');
    };

    SmokeTest::expectException(ExceptionInvalidKeyName::class, $createWithBracket);

    $createWithLongKey = function (): void {
        new MetadataItem(str_repeat('k', 41), 'x');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createWithLongKey, "40 characters");

    $createWithLongValue = function (): void {
        new MetadataItem('k', str_repeat('v', 501));
    };

    SmokeTest::expectException(ExceptionValueTooLong::class, $createWithLongValue, "too long");

    $createWithDuplicate = function (): void {
        new Metadata(new MetadataItem('k', '1'), new MetadataItem('k', '2'));
    };

    SmokeTest::expectException(ExceptionDuplicateKey::class, $createWithDuplicate, "'k'");
});

$smoke->run("PaymentIntentData", function (): void {
    $shipping = new ShippingConfig('Jo', new Address('1 High Street'), carrier: 'DHL');

    $data = new PaymentIntentData(
        applicationFeeAmount: 100,
        setupFutureUsage: PaymentIntentSetupFutureUsage::ON_SESSION,
        shippingConfig: $shipping,
        transferData: new PaymentTransferData('acct_1', 900),
    );

    $array = $data->toArray();
    SmokeTest::assertSame('automatic_async', $array['capture_method'], "capture method");
    SmokeTest::assertSame('on_session', $array['setup_future_usage'], "setup future usage");
    SmokeTest::assertSame('DHL', $array['shipping']['carrier'], "carrier");
    SmokeTest::assertSame(['destination' => 'acct_1', 'amount' => 900], $array['transfer_data'], "transfer data");
});

$smoke->run("PaymentSettings sends Stripe's values for saving the payment method", function (): void {
    $types = new PaymentMethodTypeCollection(PaymentMethodType::CARD);
    $on = (new PaymentSettings(paymentMethodTypes: $types, saveDefaultPaymentMethod: true))->toArray();
    $expected = ['payment_method_types' => ['card'], 'save_default_payment_method' => 'on_subscription'];
    SmokeTest::assertSame($expected, $on, "on");
    $off = (new PaymentSettings(saveDefaultPaymentMethod: false))->toArray();
    SmokeTest::assertSame(['save_default_payment_method' => 'off'], $off, "off");
});

$smoke->run("ProductData checks its images", function (): void {
    $images = new StringCollection('https://example.com/a.png');
    $product = (new ProductData('Shirt', images: $images))->toArray();
    SmokeTest::assertSame(['https://example.com/a.png'], $product['images'], "images");

    $createWithBadUrl = function (): void {
        new ProductData('Shirt', images: new StringCollection('not a url'));
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createWithBadUrl, "not a valid URL");

    $createWithTooManyImages = function (): void {
        new ProductData('Shirt', images: new StringCollection(...array_fill(0, 9, 'https://example.com/a.png')));
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createWithTooManyImages, "up to 8");
});

$smoke->run("prices, quantities and intervals reject impossible values", function (): void {
    $createNegativePrice = function (): void {
        new PriceDataForSinglePayment(Currency::GBP, -1, 'prod_1');
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createNegativePrice, "negative");

    $createSwappedQuantities = function (): void {
        new AdjustableQuantityConfig(10, 1);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createSwappedQuantities, "minimum");

    $createZeroInterval = function (): void {
        new RecurringConfig(RecurringInterval::MONTH, 0);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createZeroInterval, "at least 1");
});

$smoke->run("SavedPaymentMethodOptions", function (): void {
    $filters = new AllowRedisplayFiltersCollection(AllowRedisplayFilters::ALWAYS);
    $options = (new SavedPaymentMethodOptions(true, false, $filters))->toArray();

    $expected = [
        'payment_method_save' => 'enabled',
        'payment_method_remove' => 'disabled',
        'allow_redisplay_filters' => ['always'],
    ];

    SmokeTest::assertSame($expected, $options, "options");
});

$smoke->run("SubscriptionInvoiceItem", function (): void {
    $price = new PriceDataForInvoiceItem(Currency::GBP, 'prod_1', 300);
    $item = new SubscriptionInvoiceItem($price, 2, taxRates: new StringCollection('txr_1'));
    $array = (new SubscriptionInvoiceItemCollection($item))->toStripeArrayForm()[0];
    SmokeTest::assertSame(300, $array['price_data']['unit_amount'], "unit amount");
    SmokeTest::assertSame(['txr_1'], $array['tax_rates'], "tax rates");
});

$smoke->run("SubscriptionItem rejects what Stripe doesn't allow on a subscription's items", function (): void {
    $items = (new SubscriptionLineItemCollection(new SubscriptionItem(1, 'price_1')))->toStripeArrayForm();
    SmokeTest::assertSame([['quantity' => 1, 'price' => 'price_1']], $items, "items");

    $createWithDynamicTaxRates = function (): void {
        new SubscriptionItem(1, 'price_1', taxRates: new TaxRates(true, 'txr_1'));
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createWithDynamicTaxRates, "dynamic");
});

$smoke->run("SubscriptionItemDiscount", function (): void {
    $promotion = SubscriptionItemDiscount::createFromPromotionCode('promo_1')->toArray();
    SmokeTest::assertSame(['promotion_code' => 'promo_1'], $promotion, "promotion code");
    $discount = SubscriptionItemDiscount::createFromDiscountId('di_1')->toArray();
    SmokeTest::assertSame(['discount' => 'di_1'], $discount, "discount");
});

$smoke->run("TimePeriod uses inclusive comparisons by default", function (): void {
    SmokeTest::assertSame(['gte' => 1, 'lte' => 2], (new TimePeriod(1, 2))->toArray(), "inclusive");
    SmokeTest::assertSame(['gt' => 1, 'lt' => 2], (new TimePeriod(1, 2, false, false))->toArray(), "exclusive");
});

$smoke->run("SubscriptionData rejects a proration behaviour without a billing cycle anchor", function (): void {
    $createWithoutAnchor = function (): void {
        new SubscriptionData(prorationBehavior: ProrationBehavior::NONE);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $createWithoutAnchor, "billing cycle anchor");
    $none = ProrationBehavior::NONE;
    $withAnchor = (new SubscriptionData(billingCycleAnchor: 1000, prorationBehavior: $none))->toArray();
    SmokeTest::assertSame('none', $withAnchor['proration_behavior'], "proration behaviour");
});

$smoke->run("trial configs", function (): void {
    $cancel = EndTrialBehaviorIfMissingPaymentMethod::CANCEL;
    $trial = TrialConfig::createForNumDays(14, $cancel)->getStripeParams();
    $expectedSettings = ['end_behavior' => ['missing_payment_method' => 'cancel']];
    $expectedTrial = ['trial_period_days' => 14, 'trial_settings' => $expectedSettings];
    SmokeTest::assertSame($expectedTrial, $trial, "days");
    $endTrial = TrialConfig::createForEndTimestamp(5000)->getStripeParams();
    SmokeTest::assertSame(['trial_end' => 5000], $endTrial, "end timestamp");
});

$smoke->run("collections of enums", function (): void {
    $countries = (new CountryCodeCollection(CountryCode::GB, CountryCode::US))->toStripeArrayForm();
    SmokeTest::assertSame(['GB', 'US'], $countries, "countries");
    SmokeTest::assertSame([], (new StringCollection())->toArray(), "empty string collection");
});

$smoke->run("ExistingCustomer", function (): void {
    $customer = new ExistingCustomer('cus_1', new CustomerUpdateConfig(name: Updateable::AUTO));
    $expectedUpdate = ['address' => 'never', 'name' => 'auto', 'shipping' => 'never'];
    $expected = ['customer' => 'cus_1', 'customer_update' => $expectedUpdate];
    SmokeTest::assertSame($expected, $customer->getStripeParams(), "params");
});

$smoke->run("transfer data for a payment and a subscription", function (): void {
    $wholePayment = (new PaymentTransferData('acct_1'))->toArray();
    SmokeTest::assertSame(['destination' => 'acct_1'], $wholePayment, "the whole payment");
    $percent = (new SubscriptionTransferData('acct_1', 12.5))->toArray();
    SmokeTest::assertSame(['destination' => 'acct_1', 'amount_percent' => 12.5], $percent, "a percentage");

    $sendToCustomer = function (): void {
        new PaymentTransferData('cus_123', 500);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $sendToCustomer, "acct_");

    $sendNothing = function (): void {
        new PaymentTransferData('acct_1', 0);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $sendNothing, "positive");

    $sendTooMuch = function (): void {
        new SubscriptionTransferData('acct_1', 100.5);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $sendTooMuch, "0 to 100");

    $sendTooPrecise = function (): void {
        new SubscriptionTransferData('acct_1', 12.345);
    };

    SmokeTest::expectException(ExceptionInvalidValue::class, $sendTooPrecise, "two decimal places");
});
