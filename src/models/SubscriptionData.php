<?php

/*
 * A subset of parameters to be passed to subscription creation for Checkout Sessions in subscription mode.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Enums\ProrationBehavior;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;


final readonly class SubscriptionData implements Arrayable
{
    private readonly ?float $m_applicationFeePercent;
    private readonly ?int $m_billingCycleAnchor;
    private readonly ?BillingMode $m_billingMode;
    private readonly ?StringCollection $m_defaultTaxRates;
    private readonly ?string $m_description;
    private readonly ?InvoiceSettings $m_invoiceSettings;
    private readonly ?Metadata $m_metadata;
    private readonly ?string $m_onBehalfOfAccountId;
    private readonly ?ProrationBehavior $m_prorationBehavior;
    private readonly ?SubscriptionTransferData $m_transferData;
    private readonly ?TrialConfig $m_trialConfig;


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
     *
     * @param BillingMode|null $billingMode - how prorations and invoices for the subscription are calculated.
     *
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
     * @param ProrationBehavior|null $prorationBehavior - how prorations are handled.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-proration_behavior
     *
     * @param SubscriptionTransferData|null $transferData - If specified, the funds from the subscription’s invoices
     * will be transferred to the connected account, and the ID of the resulting transfers will be found on the
     * resulting charges.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-transfer_data
     *
     * @param TrialConfig|null $trialConfig - a group of settings to determine how long a trial should last and how
     * to behave when the trial ends.
     */
    public function __construct(
        ?float $applicationFeePercent = null,
        ?int $billingCycleAnchor = null,
        ?BillingMode $billingMode = null,
        ?StringCollection $defaultTaxRates = null,
        ?string $description = null,
        ?InvoiceSettings $invoiceSettings = null,
        ?Metadata $metadata = null,
        ?string $onBehalfOfAccountId = null,
        ?ProrationBehavior $prorationBehavior = null,
        ?SubscriptionTransferData $transferData = null,
        ?TrialConfig $trialConfig = null,
    )
    {
        # Stripe rejects this, so catch it here, where the mistake was made.
        if ($prorationBehavior !== null && $billingCycleAnchor === null)
        {
            throw new ExceptionInvalidValue(
                "Stripe only accepts a proration behaviour for a checkout subscription with a billing cycle anchor."
            );
        }

        $this->m_applicationFeePercent = $applicationFeePercent;
        $this->m_billingCycleAnchor = $billingCycleAnchor;
        $this->m_billingMode = $billingMode;
        $this->m_defaultTaxRates = $defaultTaxRates;
        $this->m_description = $description;
        $this->m_invoiceSettings = $invoiceSettings;
        $this->m_metadata = $metadata;
        $this->m_onBehalfOfAccountId = $onBehalfOfAccountId;
        $this->m_prorationBehavior = $prorationBehavior;
        $this->m_transferData = $transferData;
        $this->m_trialConfig = $trialConfig;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = [];

        if ($this->m_applicationFeePercent !== null)
        {
            $arrayForm['application_fee_percent'] = $this->m_applicationFeePercent;
        }

        if ($this->m_prorationBehavior !== null)
        {
            $arrayForm['proration_behavior'] = $this->m_prorationBehavior->value;
        }

        if ($this->m_invoiceSettings !== null) { $arrayForm['invoice_settings'] = $this->m_invoiceSettings->toArray(); }
        if ($this->m_billingCycleAnchor !== null) { $arrayForm['billing_cycle_anchor'] = $this->m_billingCycleAnchor; }
        if ($this->m_billingMode !== null) { $arrayForm['billing_mode'] = $this->m_billingMode->toArray(); }

        if ($this->m_defaultTaxRates !== null)
        {
            $arrayForm['default_tax_rates'] = $this->m_defaultTaxRates->toArray();
        }

        if ($this->m_description !== null) { $arrayForm['description'] = $this->m_description; }
        if ($this->m_metadata !== null) { $arrayForm['metadata'] = $this->m_metadata->toStripeArrayForm(); }
        if ($this->m_onBehalfOfAccountId !== null) { $arrayForm['on_behalf_of'] = $this->m_onBehalfOfAccountId; }

        if ($this->m_transferData !== null)
        {
            $arrayForm['transfer_data'] = $this->m_transferData->toArray();
        }

        if ($this->m_trialConfig !== null)
        {
            $arrayForm = array_merge($arrayForm, $this->m_trialConfig->getStripeParams());
        }

        return $arrayForm;
    }
}
