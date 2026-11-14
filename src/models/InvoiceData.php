<?php

/*
 * The settings for an invoice created after a one-time payment.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation-invoice_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Interfaces\Arrayable;

readonly class InvoiceData implements Arrayable
{
    private ?StringCollection $m_accountTaxIds;
    private ?InvoiceCustomFieldCollection $m_customFields;
    private ?string $m_description;
    private ?string $m_footer;
    private ?AccountReference $m_issuer;
    private ?Metadata $m_metadata;
    private ?InvoiceRenderingOptions $m_renderingOptions;


    /**
     * Create an InvoiceData object for invoice creation.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation-invoice_data
     *
     * @param ?StringCollection $accountTaxIds - The account tax IDs associated with the invoice.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation-invoice_data-account_tax_ids
     *
     * @param InvoiceCustomFieldCollection|null $customFields - Default custom fields to be displayed on invoices for
     * this customer. A copy is kept, so changing the collection afterwards doesn't change this object.
     *
     * @param string|null $description - An arbitrary string attached to the object. Often useful for displaying to
     * users.
     *
     * @param string|null $footer - a basic string to put on the invoice as a footer. This is not meant to take HTML.
     *
     * @param AccountReference|null $issuer - the connected account that issues the invoice, if using Stripe Connect.
     *
     * @param Metadata|null $metadata - Set of key-value pairs that you can attach to an object. This can be useful for
     * storing additional information about the object in a structured format. A copy is kept, so changing the
     * collection afterwards doesn't change this object.
     *
     * @param InvoiceRenderingOptions|null $renderingOptions - optionally, how the invoice PDF is rendered.
     */
    public function __construct(
        ?StringCollection $accountTaxIds = null,
        ?InvoiceCustomFieldCollection $customFields = null,
        ?string $description = null,
        ?string $footer = null,
        ?AccountReference $issuer = null,
        ?Metadata $metadata = null,
        ?InvoiceRenderingOptions $renderingOptions = null,
    )
    {
        $this->m_accountTaxIds = $accountTaxIds;
        $this->m_customFields = ($customFields !== null) ? clone $customFields : null;
        $this->m_description = $description;
        $this->m_footer = $footer;
        $this->m_issuer = $issuer;
        $this->m_metadata = ($metadata !== null) ? clone $metadata : null;
        $this->m_renderingOptions = $renderingOptions;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = [];

        if ($this->m_accountTaxIds !== null) { $arrayForm['account_tax_ids'] = $this->m_accountTaxIds->toArray(); }

        if ($this->m_customFields !== null)
        {
            $arrayForm['custom_fields'] = $this->m_customFields->toStripeArrayForm();
        }

        if ($this->m_description !== null) { $arrayForm['description'] = $this->m_description; }
        if ($this->m_footer !== null) { $arrayForm['footer'] = $this->m_footer; }
        if ($this->m_issuer !== null) { $arrayForm['issuer'] = $this->m_issuer->toArray(); }
        if ($this->m_metadata !== null) { $arrayForm['metadata'] = $this->m_metadata->toStripeArrayForm(); }

        if ($this->m_renderingOptions !== null)
        {
            $arrayForm['rendering_options'] = $this->m_renderingOptions->toArray();
        }

        return $arrayForm;
    }
}
