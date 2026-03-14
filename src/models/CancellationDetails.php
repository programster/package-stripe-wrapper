<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\CancellationFeedback;
use Programster\Stripe\Enums\ExceptionMissingRequiredParameter;
use Programster\Stripe\Interfaces\Arrayable;

readonly class CancellationDetails implements Arrayable
{

    /**
     * Controls how prorations and invoices for subscriptions are calculated and orchestrated.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-subscription_data-billing_mode
     */
    public function __construct(
        private ?string               $userComment = null,
        private ?CancellationFeedback $feedback = null,
    )
    {
        if ($userComment === null && $feedback === null)
        {
            throw new ExceptionMissingRequiredParameter("Must provide at least a comment or feedback for cancellation details.");
        }
    }


    public function toArray(): array
    {
        $arrayForm = [];

        if ($this->feedback !== null) { $arrayForm['feedback'] = $this->feedback->value; }
        if ($this->userComment !== null) { $arrayForm['comment'] = $this->userComment; }

        return $arrayForm;
    }
}