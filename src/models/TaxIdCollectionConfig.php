<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class TaxIdCollectionConfig implements Arrayable
{
    /**
     * Controls tax ID collection during checkout.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-tax_id_collection
     * @param bool $enabled - Enable tax ID collection during checkout.
     * @param bool $required - Describes whether a tax ID is required during checkout if Stripe supports collecting
     * a tax id for the selected billing address country.
     */
    public function __construct(
        private readonly bool $enabled,
        private readonly bool $required
    )
    {

    }


    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'required' => ($this->required) ? "if_supported" : "never",
        ];
    }
}