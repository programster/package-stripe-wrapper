<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class InvoiceCreation implements Arrayable
{
    /**
     * Generate a post-purchase Invoice for one-time payments. This constructor is private as one needs to use
     * one of the create static methods for creating this object.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation
     * @param bool $enabled - whether invoices should be created.
     * @param InvoiceData - the settings for invoices, which is required if enabled is set to true (hence the need)
     * for the two different static create methods.
     */
    private function __construct(private bool $enabled, private ?InvoiceData $invoiceData)
    {

    }

    public static function createEnabled(InvoiceData $invoiceData): self
    {
        return new InvoiceCreation(true, $invoiceData);
    }

    public static function createDisabled() : self
    {
        return new InvoiceCreation(false, null);
    }


    public function isEnabled(): bool
    {
        return $this->enabled;
    }


    public function toArray(): array
    {
        $arrayForm = [
            "enabled" => $this->enabled,
        ];

        if ($this->invoiceData !== null)
        {
            $arrayForm["invoice_data"] = $this->invoiceData->toArray();
        }

        return $arrayForm;
    }
}
