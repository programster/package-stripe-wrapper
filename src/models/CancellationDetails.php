<?php

/*
 * Details of why a customer cancelled a subscription.
 * https://docs.stripe.com/api/subscriptions/cancel#cancel_subscription-cancellation_details
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Enums\CancellationFeedback;
use Programster\Stripe\Exceptions\ExceptionMissingRequiredParameter;
use Programster\Stripe\Interfaces\Arrayable;

final readonly class CancellationDetails implements Arrayable
{
    private ?string $m_userComment;
    private ?CancellationFeedback $m_feedback;


    /**
     * Record why the customer cancelled. At least one of the two must be given.
     * @param string|null $userComment - optionally, the customer's own comment on why they cancelled.
     * @param CancellationFeedback|null $feedback - optionally, the reason the customer chose from a fixed list.
     * @throws ExceptionMissingRequiredParameter - if neither is given.
     */
    public function __construct(?string $userComment = null, ?CancellationFeedback $feedback = null)
    {
        if ($userComment === null && $feedback === null)
        {
            throw new ExceptionMissingRequiredParameter(
                "Must provide at least a comment or feedback for cancellation details."
            );
        }

        $this->m_userComment = $userComment;
        $this->m_feedback = $feedback;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = [];

        if ($this->m_feedback !== null) { $arrayForm['feedback'] = $this->m_feedback->value; }
        if ($this->m_userComment !== null) { $arrayForm['comment'] = $this->m_userComment; }

        return $arrayForm;
    }
}
