<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class ShippingConfig implements Arrayable
{
    /**
     * Create a payment intent shipping configuration.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data-shipping
     *
     * @param string $recipientName - the name of the recipient for the address. This may or may not be the same as the
     * name of the customer. E.g. someone may buy and ship something to somebody else as a gift.
     *
     * @param Address $shippingAddress - the address for where to ship.
     *
     * @param string|null $customerPhone - the recipient phone number (including extension).
     *
     * @param string|null $carrier - The delivery service that shipped a physical product, such as Fedex, UPS, USPS, etc.
     *
     * @param string|null $trackingNumber - The tracking number for a physical product, obtained from the delivery
     * service. If multiple tracking numbers were generated for this purchase, please separate them with commas.
     */
    public function __construct(
        private readonly string  $recipientName,
        private readonly Address $shippingAddress,
        private readonly ?string $customerPhone = null,
        private readonly ?string $carrier = null,
        private readonly ?string $trackingNumber = null,
    )
    {

    }

    public function toArray(): array
    {
        $arrayForm = [
            'name' => $this->recipientName,
            'address' => $this->shippingAddress->toArray(),
        ];

        if ($this->carrier !== null)
        {
            $arrayForm['carrier'] = $this->carrier;
        }

        if ($this->trackingNumber !== null)
        {
            $arrayForm['tracking_number'] = $this->trackingNumber;
        }

        if ($this->customerPhone !== null)
        {
            $arrayForm['phone'] = $this->customerPhone;
        }

        return $arrayForm;
    }
}