<?php

/*
 * The shipping information for a payment.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-payment_intent_data-shipping
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;


readonly class ShippingConfig implements Arrayable
{
    private string $m_recipientName;
    private Address $m_shippingAddress;
    private ?string $m_customerPhone;
    private ?string $m_carrier;
    private ?string $m_trackingNumber;


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
     * @param string|null $carrier - The delivery service that shipped a physical product, such as Fedex, UPS, USPS,
     * etc.
     *
     * @param string|null $trackingNumber - The tracking number for a physical product, obtained from the delivery
     * service. If multiple tracking numbers were generated for this purchase, please separate them with commas.
     */
    public function __construct(
        string $recipientName,
        Address $shippingAddress,
        ?string $customerPhone = null,
        ?string $carrier = null,
        ?string $trackingNumber = null,
    )
    {
        $this->m_recipientName = $recipientName;
        $this->m_shippingAddress = $shippingAddress;
        $this->m_customerPhone = $customerPhone;
        $this->m_carrier = $carrier;
        $this->m_trackingNumber = $trackingNumber;
    }


    public function toArray() : array
    {
        $arrayForm = [
            'name' => $this->m_recipientName,
            'address' => $this->m_shippingAddress->toArray(),
        ];

        if ($this->m_carrier !== null)
        {
            $arrayForm['carrier'] = $this->m_carrier;
        }

        if ($this->m_trackingNumber !== null)
        {
            $arrayForm['tracking_number'] = $this->m_trackingNumber;
        }

        if ($this->m_customerPhone !== null)
        {
            $arrayForm['phone'] = $this->m_customerPhone;
        }

        return $arrayForm;
    }
}
