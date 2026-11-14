<?php

/*
 * Whether a customer can save their payment method in a checkout session, or remove one they saved before.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-saved_payment_method_options
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\AllowRedisplayFiltersCollection;
use Programster\Stripe\Exceptions\ExceptionMissingRequiredParameter;
use Programster\Stripe\Interfaces\Arrayable;


class SavedPaymentMethodOptions implements Arrayable
{
    private readonly ?bool $m_enableSavingOfPaymentMethod;
    private readonly ?bool $m_enablePaymentMethodRemoval;
    private readonly ?AllowRedisplayFiltersCollection $m_allowRedisplayFilters;


    /**
     * Provide the configuration for whether we wish to allow the user to save or remove the saving of their payment
     * method. E.g. "save card details".
     *
     * @param bool|null $enableSavingOfPaymentMethod - allow the user to save their payment method.
     *
     * @param bool|null $enablePaymentMethodRemoval - allow the user to remove their saved payment method.
     *
     * @param AllowRedisplayFiltersCollection|null $allowRedisplayFilters - provide the collection of payment method
     * collection consent filters that are acceptable for saving details for the user. This is super confusing.
     * allow_redisplay_filters is an array because it acts as a filter list — you can specify multiple
     * allow_redisplay values that Stripe should include when deciding which saved payment methods to show/prefill in
     * the Checkout Session.
     *  - always - Customer explicitly agreed to save for future purchases (Full consent)
     *  - limited = Saved but with limited reuse rights (e.g. for the current subscription) (Restricted)
     *  - unspecified - Legacy / older saved methods where consent type wasn't recorded (Unknown)
     * If you are ever in doubt, I would either leave this as null, or provide a collection of just one
     * allowRedisplayFilter element that is set to "always".
     *
     * @throws ExceptionMissingRequiredParameter - if every parameter is null.
     */
    public function __construct(
        ?bool $enableSavingOfPaymentMethod = true,
        ?bool $enablePaymentMethodRemoval = true,
        ?AllowRedisplayFiltersCollection $allowRedisplayFilters = null
    )
    {
        if (
               $enableSavingOfPaymentMethod === null
            && $enablePaymentMethodRemoval === null
            && $allowRedisplayFilters === null
        )
        {
            throw new ExceptionMissingRequiredParameter(
                "You need to provide at least one value that is not null to SavedPaymentMethodOptions"
            );
        }

        $this->m_enableSavingOfPaymentMethod = $enableSavingOfPaymentMethod;
        $this->m_enablePaymentMethodRemoval = $enablePaymentMethodRemoval;
        $this->m_allowRedisplayFilters = ($allowRedisplayFilters !== null) ? clone $allowRedisplayFilters : null;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = [];

        if ($this->m_enableSavingOfPaymentMethod !== null)
        {
            $arrayForm['payment_method_save'] = ($this->m_enableSavingOfPaymentMethod) ? "enabled" : "disabled";
        }

        if ($this->m_enablePaymentMethodRemoval !== null)
        {
            $arrayForm['payment_method_remove'] = ($this->m_enablePaymentMethodRemoval) ? "enabled" : "disabled";
        }

        if ($this->m_allowRedisplayFilters !== null)
        {
            $arrayForm['allow_redisplay_filters'] = $this->m_allowRedisplayFilters->toStripeArrayForm();
        }

        return $arrayForm;
    }
}
