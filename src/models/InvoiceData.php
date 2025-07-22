<?php

/*
 * Create an AfterExpiration object for a checkout session. This configures whether you will allow the user to
 * recover after a session expires, and whether this recovered session should include any promo codes the user
 * may have had.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-after_expiration
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Interfaces\Arrayable;

class InvoiceData implements Arrayable
{
    /**
     * Create an InvoiceData object for invoice creation. Please note that this object deliberately leaves out
     * "issuer" as that is injected elsewhere in the package based on the StripeConnectConfig that is passed
     * elsewhere.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation-invoice_data
     *
     * @param ?StringCollection $accountTaxIds - The account tax IDs associated with the invoice.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation-invoice_data-account_tax_ids
     *
     * @param CustomFieldCollection|null $customFields - Default custom fields to be displayed on invoices for this
     * customer.
     *
     * @param string|null $description - An arbitrary string attached to the object. Often useful for displaying to
     * users.
     *
     * @param string|null $footer - a basic string to put on the invoice as a footer. This is not meant to take HTML.
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful for
     * storing additional information about the object in a structured format. Individual keys can be unset by posting
     * an empty value to them. All keys can be unset by posting an empty value to metadata.
     *
     * @param bool|null $showTax - How line-item prices and amounts will be displayed with respect to tax on invoice
     * PDFs. One of exclude_tax (false) or include_inclusive_tax (true). include_inclusive_tax will include inclusive
     * tax (and exclude exclusive tax) in invoice PDF amounts. exclude_tax will exclude all tax (inclusive and
     * exclusive alike) from invoice PDF amounts.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation-invoice_data-rendering_options-amount_tax_display
     */
    private function __construct(
        private readonly ?StringCollection $accountTaxIds = null,
        private readonly ?InvoiceCustomFieldCollection $customFields = null,
        private readonly ?string $description = null,
        private readonly ?string $footer = null,
        private readonly ?Metadata $metadata = null,
        private readonly ?bool $showTax,
    )
    {

    }


    public function toArray(): array
    {
        $recoveryObj = [
            "enabled" => $this->enabled,
        ];

        if ($this->allowPromotionCodesInRecovery !== null)
        {
            $recoveryObj["allowPromotionCodesInRecovery"] = $this->allowPromotionCodesInRecovery;
        }

        return ["recovery" => $recoveryObj];
    }
}