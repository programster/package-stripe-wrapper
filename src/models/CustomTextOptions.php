<?php

/*
 * Custom text to show the customer at various points during checkout.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-custom_text
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Exceptions\ExceptionMissingRequiredParameter;
use Programster\Stripe\Exceptions\ExceptionValueTooLong;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomTextOptions implements Arrayable
{
    private ?string $m_afterSubmit;
    private ?string $m_shippingAddress;
    private ?string $m_submit;
    private ?string $m_termsOfServiceAcceptance;


    /**
     * Display additional text for your customers using custom text.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-custom_text
     *
     * @param string|null $afterSubmit - Custom text that should be displayed after the payment confirmation button.
     *
     * @param string|null $shippingAddress - Custom text that should be displayed alongside shipping address collection.
     *
     * @param string|null $submit - Custom text that should be displayed alongside the payment confirmation button.
     *
     * @param string|null $termsOfServiceAcceptance - Custom text that should be displayed in place of the default terms
     * of service agreement text.
     *
     * @throws ExceptionValueTooLong - if any of the messages is longer than 1200 characters.
     *
     * @throws ExceptionMissingRequiredParameter - if all the optional parameters are set to null. At least one must
     * be specified if you are creating one of these objects.
     */
    public function __construct(
        ?string $afterSubmit = null,
        ?string $shippingAddress = null,
        ?string $submit = null,
        ?string $termsOfServiceAcceptance = null,
    )
    {
        foreach ([$afterSubmit, $shippingAddress, $submit, $termsOfServiceAcceptance] as $parameter)
        {
            if ($parameter !== null && strlen($parameter) > 1200)
            {
                throw new ExceptionValueTooLong(
                    $parameter,
                    "Custom text options must not have a length longer than 1200 characters."
                );
            }
        }

        $this->m_afterSubmit = $afterSubmit;
        $this->m_shippingAddress = $shippingAddress;
        $this->m_submit = $submit;
        $this->m_termsOfServiceAcceptance = $termsOfServiceAcceptance;

        if (count($this->toArray()) === 0)
        {
            throw new ExceptionMissingRequiredParameter(
                "You must provide at least one of the optional parameters for a CustomTextOptions object."
            );
        }
    }


    public function toArray() : array
    {
        $arrayForm = [];

        if ($this->m_afterSubmit !== null)
        {
            $arrayForm['after_submit'] = ['message' => $this->m_afterSubmit];
        }

        if ($this->m_shippingAddress !== null)
        {
            $arrayForm['shipping_address'] = ['message' => $this->m_shippingAddress];
        }

        if ($this->m_submit !== null)
        {
            $arrayForm['submit'] = ['message' => $this->m_submit];
        }

        if ($this->m_termsOfServiceAcceptance !== null)
        {
            $arrayForm['terms_of_service_acceptance'] = ['message' => $this->m_termsOfServiceAcceptance];
        }

        return $arrayForm;
    }
}
