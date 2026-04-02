<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\BillingModeType;
use Programster\Stripe\Exceptions\ExceptionMissingRequiredParameter;
use Programster\Stripe\Enums\ProrationDiscountConfig;
use Programster\Stripe\Interfaces\Arrayable;

readonly class InvoiceRenderingOptions implements Arrayable
{
    /**
     * Default options for invoice PDF rendering for this customer.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation-invoice_data-rendering_options
     *
     * @param ?bool $includeTax - How line-item prices and amounts will be displayed with respect to tax on invoice
     * PDFs. One of exclude_tax (false) or include_inclusive_tax (true). include_inclusive_tax will include inclusive
     * tax (and exclude exclusive tax) in invoice PDF amounts. exclude_tax will exclude all tax (inclusive and
     * exclusive alike) from invoice PDF amounts.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-invoice_creation-invoice_data-rendering_options-amount_tax_display
     *
     * @param string|null $templateId - ID of the invoice rendering template to use for this invoice.
     *
     * @throws ExceptionMissingRequiredParameter - if neither of the optional values are provided. You must specify something.
     */
    private function __construct(
        private ?bool $includeTax,
        private ?string $templateId = null
    )
    {
        if ($includeTax === null && $templateId === null)
        {
            throw new ExceptionMissingRequiredParameter(
                "You must provide a value for either including tax, or the template ID."
            );
        }
    }


    public function toArray(): array
    {
        $arrayForm = [];

        if ($this->includeTax !== null) { $arrayForm['amount_tax_display'] = ($this->includeTax) ? "include_inclusive_tax" : "exclude_tax"; }
        if ($this->templateId === null) { $arrayForm['template_id'] = $this->templateId; }

        return $arrayForm;
    }
}