<?php

/*
 * Whether a checkout session collects the customer's tax ID.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-tax_id_collection
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;


readonly class TaxIdCollectionConfig implements Arrayable
{
    private bool $m_enabled;
    private bool $m_required;


    /**
     * Controls tax ID collection during checkout.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-tax_id_collection
     *
     * @param bool $enabled - Enable tax ID collection during checkout.
     *
     * @param bool $required - Describes whether a tax ID is required during checkout if Stripe supports collecting
     * a tax id for the selected billing address country.
     */
    public function __construct(bool $enabled, bool $required)
    {
        $this->m_enabled = $enabled;
        $this->m_required = $required;
    }


    public function toArray() : array
    {
        return [
            'enabled' => $this->m_enabled,
            'required' => ($this->m_required) ? "if_supported" : "never",
        ];
    }
}
