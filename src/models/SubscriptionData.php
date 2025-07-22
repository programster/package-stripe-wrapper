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
     * @param string|null $description - optionally set a description of the subscription to show to the user when
     * they see it in the customer portal.
     *
     * @param TrialConfig|null $trialConfig
     *
     * @param ProrationBehavior $prorationBehavior
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-proration_behavior
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
     * @param StringCollection|null $defaultTaxRates - The tax rates that will apply to any subscription item that does
     * not have tax_rates set. Invoices created will have their default_tax_rates populated from the subscription.
     *
     * @param AccountReference|null $issuerForConnect - The connected account that issues the invoice. The invoice is
     * presented with the branding and support information of the specified account.
     *
     * @param string|null $onBehalfOfAccountId - specify the account ID of the Stripe account these subscriptions are
     * on behalf of. E.g. if this is a "Stripe connect" setup, this would be the account of the person doing the work
     * that is being paid for, not the ID of the account of the team that built the application/platform.
     *
     * @param TransferData|null $stripeConnectConfig - If specified, the funds from the subscription’s invoices will be
     * transferred to the destination and the ID of the resulting transfers will be found on the resulting charges.
     *
     * @param Metadata|null $metadata - optionally store some metadata against the subscription.
     *
     */
    public function __construct(
        private readonly ?string                     $description = null,
        private readonly ?TrialConfig                $trialConfig = null,
        private readonly ProrationBehavior           $prorationBehavior = ProrationBehavior::CREATE,
        private readonly ?int                        $billingCycleAnchor = null,
        private readonly ?StringCollection           $defaultTaxRates = null,
        private readonly ?StripeConnectPaymentConfig $stripeConnectConfig = null,
        private readonly ?Metadata                   $metadata = null,
    )
    {
    }

    public function toArray(): array
    {
        $arrayForm = [
            'proration_behavior' => $this->prorationBehavior->value,
        ];

        if ($this->stripeConnectConfig !== null)
        {
            $arrayForm = array_merge($arrayForm, $this->stripeConnectConfig->getParams());

            $arrayForm['invoice_settings'] = [
                'issuer' => $this->stripeConnectConfig->getIssuerObj()
            ];
        }

        if ($this->billingCycleAnchor !== null)
        {
            $arrayForm['billing_cycle_anchor'] = $this->billingCycleAnchor;
        }

        if ($this->defaultTaxRates !== null)
        {
            $arrayForm['default_tax_rates'] = $this->defaultTaxRates->toArray();
        }

        if ($this->description !== null)
        {
            $arrayForm['description'] = $this->description;
        }

        if ($this->metadata !== null)
        {
            $arrayForm['metadata'] = $this->metadata->toStripeArrayForm();
        }

        if ($this->trialConfig !== null)
        {
            $arrayForm = array_merge($arrayForm, $this->trialConfig->getStripeParams());
        }

        return $arrayForm;
    }
}