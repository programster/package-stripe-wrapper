<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Interfaces\Arrayable;
use Stripe\InvoiceRenderingTemplate;

readonly class InvoiceData implements Arrayable
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
     * @param InvoiceCustomFieldCollection|null $customFields - Default custom fields to be displayed on invoices for this
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
     * @param InvoiceRenderingOptions|null $renderingOptions -
     */
    public function __construct(
        private ?StringCollection $accountTaxIds = null,
        private ?InvoiceCustomFieldCollection $customFields = null,
        private ?string $description = null,
        private ?string $footer = null,
        private ?AccountReference $issuer = null,
        private ?Metadata $metadata = null,
        private ?InvoiceRenderingOptions $renderingOptions = null,
    )
    {

    }


    public function toArray(): array
    {
        $arrayForm = [];

        if ($this->accountTaxIds    !== null) { $arrayForm['account_tax_ids'] = $this->accountTaxIds->toArray(); }
        if ($this->customFields     !== null) { $arrayForm['custom_fields'] = $this->customFields->toStripeArrayForm(); }
        if ($this->description      !== null) { $arrayForm['description'] = $this->description; }
        if ($this->footer           !== null) { $arrayForm['footer'] = $this->footer; }
        if ($this->issuer           !== null) { $arrayForm['issuer'] = $this->issuer->toArray(); }
        if ($this->metadata         !== null) { $arrayForm['metadata'] = $this->metadata; }
        if ($this->renderingOptions !== null) { $arrayForm['rendering_options'] = $this->renderingOptions->toArray(); }

        return $arrayForm;
    }
}