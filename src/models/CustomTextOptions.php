<?php

/*
 * An object to reference self or another account. This can be used for providing information about tax liability or
 * issuer data for a "Stripe Connect" invoice.
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\ExceptionMissingRequiredParameter;
use Programster\Stripe\Enums\ExceptionValueTooLong;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CustomTextOptions implements Arrayable
{
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
     * @throws ExceptionMissingRequiredParameter - if all the optional parameters are set to null. At lest one must
     * be specified if you are creating one of these objects.
     */
    public function __construct(
        private ?string $afterSubmit = null,
        private ?string $shippingAddress = null,
        private ?string $submit = null,
        private ?string $termsOfServiceAcceptance = null,
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

        if (count($this->toArray()) === 0)
        {
            throw new ExceptionMissingRequiredParameter(
                "You must provide at least one of the optional parameters for a CustomTextOptions object."
            );
        }
    }


    public function toArray(): array
    {
        $arrayForm = [];

        if ($this->afterSubmit !== null)
        {
            $arrayForm['after_submit'] = [
                'message' => $this->afterSubmit,
            ];
        }

        if ($this->shippingAddress !== null)
        {
            $arrayForm['shipping_address'] = [
                'message' => $this->shippingAddress,
            ];
        }

        if ($this->submit !== null)
        {
            $arrayForm['submit'] = [
                'message' => $this->submit,
            ];
        }

        if ($this->termsOfServiceAcceptance !== null)
        {
            $arrayForm['terms_of_service_acceptance'] = [
                'message' => $this->submit,
            ];
        }

        return $arrayForm;
    }
}