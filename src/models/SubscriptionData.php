<?php

/*
 * A subset of parameters to be passed to subscription creation for Checkout Sessions in subscription mode.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Enums\ExceptionMissingRequiredParameter;
use Programster\Stripe\Enums\ProrationBehavior;

class SubscriptionData implements \Programster\Stripe\Interfaces\Arrayable
{
    /**
     * Create a SubscriptionData object that specifies a subset of parameters to be passed to subscription creation for
     * Checkout Sessions in subscription mode.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data
     *
     * @param float|null $applicationFeePercent - A non-negative decimal between 0 and 100, with at most two decimal
     * places. This represents the percentage of the subscription invoice total that will be transferred to the
     * application owner’s Stripe account. To use an application fee percent, the request must be made on behalf of
     * another account, using the Stripe-Account header or an OAuth key. For more information, see the application
     * fees documentation. https://docs.stripe.com/connect/subscriptions#collecting-fees-on-subscriptions
     *
     * @param int|null $billingCycleAnchor - A future timestamp to anchor the subscription’s billing cycle for new
     * subscriptions. If not provided, the subscription will default to the billing cycle starting immediately.
     * @param BillingMode|null $billingMode
     * @param StringCollection|null $defaultTaxRates - The tax rates that will apply to any subscription item that does
     * not have tax_rates set. Invoices created will have their default_tax_rates populated from the subscription.
     *
     * @param string|null $description - optionally set a description of the subscription to show to the user when
     * they see it in the customer portal.
     *
     * @param InvoiceSettings|null $invoiceSettings - All invoices will be billed using the specified settings.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-invoice_settings
     *
     * @param Metadata|null $metadata - optionally store some metadata against the subscription.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-metadata
     *
     * @param string|null $onBehalfOfAccountId - specify the account ID of the Stripe account these subscriptions are
     * on behalf of. E.g. if this is a "Stripe connect" setup, this would be the account of the person doing the work
     * that is being paid for, not the ID of the account of the team that built the application/platform.
     *
     * @param ProrationBehavior|null $prorationBehavior
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-proration_behavior
     *
     * @param TransferData|null $transferData - If specified, the funds from the subscription’s invoices will be
     * transferred to the destination and the ID of the resulting transfers will be found on the resulting charges.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-transfer_data
     *
     * @param TrialConfig|null $trialConfig - a group of settings to determine how long a trial should last and how
     * to behave when the trial ends.
     */
    public function __construct(
        private readonly ?float             $applicationFeePercent = null,
        private readonly ?int               $billingCycleAnchor = null,
        private readonly ?BillingMode       $billingMode = null,
        private readonly ?StringCollection  $defaultTaxRates = null,
        private readonly ?string            $description = null,
        private readonly ?InvoiceSettings   $invoiceSettings = null,
        private readonly ?Metadata          $metadata = null,
        private readonly ?string            $onBehalfOfAccountId = null,
        private readonly ?ProrationBehavior $prorationBehavior = null,
        private readonly ?TransferData      $transferData = null,
        private readonly ?TrialConfig       $trialConfig = null,
    )
    {
    }

    public function toArray(): array
    {
        $arrayForm = [];

        if ($this->applicationFeePercent !== null) { $arrayForm['application_fee_percent'] = $this->applicationFeePercent; }
        if ($this->prorationBehavior     !== null) { $arrayForm['proration_behavior'] = $this->prorationBehavior->value; }
        if ($this->invoiceSettings       !== null) { $arrayForm['invoice_settings'] = $this->invoiceSettings->toArray(); }
        if ($this->billingCycleAnchor    !== null) { $arrayForm['billing_cycle_anchor'] = $this->billingCycleAnchor; }
        if ($this->billingMode           !== null) { $arrayForm['billing_mode'] = $this->billingMode->toArray(); }
        if ($this->defaultTaxRates       !== null) { $arrayForm['default_tax_rates'] = $this->defaultTaxRates->toArray(); }
        if ($this->description           !== null) { $arrayForm['description'] = $this->description; }
        if ($this->metadata              !== null) { $arrayForm['metadata'] = $this->metadata->toStripeArrayForm(); }
        if ($this->onBehalfOfAccountId   !== null) { $arrayForm['on_behalf_of'] = $this->onBehalfOfAccountId; }
        if ($this->transferData          !== null) { $arrayForm['transfer_data'] = $this->transferData->toArray(); }

        if ($this->trialConfig !== null)
        {
            $arrayForm = array_merge($arrayForm, $this->trialConfig->getStripeParams());
        }

        return $arrayForm;
    }
}