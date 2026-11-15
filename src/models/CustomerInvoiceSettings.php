<?php

/*
 * Default invoice settings for a customer.
 * https://docs.stripe.com/api/customers/create#create_customer-invoice_settings
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Interfaces\Arrayable;

final readonly class CustomerInvoiceSettings implements Arrayable
{
    private ?InvoiceCustomFieldCollection $m_customFields;
    private ?string $m_defaultPaymentMethodId;
    private ?string $m_footer;
    private ?InvoiceRenderingOptions $m_renderingOptions;


    /**
     * Default invoice settings for this customer.
     * https://docs.stripe.com/api/customers/create?lang=php#create_customer-invoice_settings
     *
     * @param InvoiceCustomFieldCollection|null $customFields - optionally, custom fields to show on the customer's
     * invoices.
     *
     * @param string|null $defaultPaymentMethodId - optionally, the ID of the payment method to use for the customer's
     * invoices and subscriptions by default.
     *
     * @param string|null $footer - optionally, a footer to show on the customer's invoices.
     *
     * @param InvoiceRenderingOptions|null $renderingOptions - optionally, how the customer's invoice PDFs are rendered.
     */
    public function __construct(
        ?InvoiceCustomFieldCollection $customFields = null,
        ?string $defaultPaymentMethodId = null,
        ?string $footer = null,
        ?InvoiceRenderingOptions $renderingOptions = null,
    )
    {
        $this->m_customFields = $customFields;
        $this->m_defaultPaymentMethodId = $defaultPaymentMethodId;
        $this->m_footer = $footer;
        $this->m_renderingOptions = $renderingOptions;
    }


    /**
     * @return array{
     *     custom_fields?: list<array{name: string, value: string}>,
     *     default_payment_method?: string,
     *     footer?: string,
     *     rendering_options?: array{amount_tax_display?: string, template_id?: string}
     * }
     */
    public function toArray() : array
    {
        $arrayForm = [];

        if ($this->m_customFields !== null)
        {
            $arrayForm['custom_fields'] = $this->m_customFields->toStripeArrayForm();
        }

        if ($this->m_defaultPaymentMethodId !== null)
        {
            $arrayForm['default_payment_method'] = $this->m_defaultPaymentMethodId;
        }

        if ($this->m_footer !== null)
        {
            $arrayForm['footer'] = $this->m_footer;
        }

        if ($this->m_renderingOptions !== null)
        {
            $arrayForm['rendering_options'] = $this->m_renderingOptions->toArray();
        }

        return $arrayForm;
    }
}
