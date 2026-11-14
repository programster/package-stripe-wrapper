<?php

/*
 * Whether to generate a post-purchase invoice for a one-time payment.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class InvoiceCreation implements Arrayable
{
    private bool $m_enabled;
    private ?InvoiceData $m_invoiceData;


    /**
     * Generate a post-purchase Invoice for one-time payments. This constructor is private as one needs to use
     * one of the create static methods for creating this object.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation
     *
     * @param bool $enabled - whether invoices should be created.
     *
     * @param InvoiceData|null $invoiceData - the settings for invoices, which is required if enabled is set to true
     * (hence the need for the two different static create methods).
     */
    private function __construct(bool $enabled, ?InvoiceData $invoiceData)
    {
        $this->m_enabled = $enabled;
        $this->m_invoiceData = $invoiceData;
    }


    /**
     * Create an invoice after the purchase, with these settings.
     * @param InvoiceData $invoiceData - the settings for the invoice.
     * @return InvoiceCreation
     */
    public static function createEnabled(InvoiceData $invoiceData) : InvoiceCreation
    {
        return new InvoiceCreation(true, $invoiceData);
    }


    /**
     * Don't create an invoice after the purchase.
     * @return InvoiceCreation
     */
    public static function createDisabled() : InvoiceCreation
    {
        return new InvoiceCreation(false, null);
    }


    public function toArray() : array
    {
        $arrayForm = [
            "enabled" => $this->m_enabled,
        ];

        if ($this->m_invoiceData !== null)
        {
            $arrayForm["invoice_data"] = $this->m_invoiceData->toArray();
        }

        return $arrayForm;
    }


    # Accessors
    public function isEnabled() : bool { return $this->m_enabled; }
}
