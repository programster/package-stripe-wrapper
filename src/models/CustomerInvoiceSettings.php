<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomerInvoiceSettings implements Arrayable
{
    /**
     * Default invoice settings for this customer.
     * https://docs.stripe.com/api/customers/create?lang=php#create_customer-invoice_settings
     */
    public function __construct(
        private ?InvoiceCustomFieldCollection $customFields = null,
        private ?string $defaultPaymentMethodId = null,
        private ?string $footer = null,
        private ?InvoiceRenderingOptions $renderingOptions = null,
    )
    {

    }


    public function toArray(): array
    {
        $arrayForm = [];

        if ($this->customFields           !== null) { $arrayForm['custom_fields'] = $this->customFields->toStripeArrayForm(); }
        if ($this->defaultPaymentMethodId !== null) { $arrayForm['default_payment_method'] = $this->defaultPaymentMethodId; }
        if ($this->footer                 !== null) { $arrayForm['footer'] = $this->footer; }
        if ($this->renderingOptions       !== null) { $arrayForm['rendering_options'] = $this->renderingOptions->toArray(); }

        return $arrayForm;
    }
}