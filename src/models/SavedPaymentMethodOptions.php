<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\AllowRedisplayFiltersCollection;
use Programster\Stripe\Exceptions\ExceptionMissingRequiredParameter;
use Programster\Stripe\Interfaces\Arrayable;

class SavedPaymentMethodOptions implements Arrayable
{
    /**
     * Provide the configuration for whether we wish to allow the user to save or remove the saving of their payment
     * method. E.g. "save card details".
     *
     * @param bool $enableSavingOfPaymentMethod - allow the user to save their payment method
     *
     * @param bool $enablePaymentMethodRemoval - allow the user to remove their saved payment method.
     *
     * @param \Programster\Stripe\Models\AllowRedisplayFiltersCollection|null $allowRedisplayFilters - provide the
     * collection of payment method collection consent filters that are acceptable for saving details for the user
     * This is super confusing. allow_redisplay_filters is an array because it acts as a filter list — you can
     * specify multiple allow_redisplay values that Stripe should include when deciding which saved payment methods
     * to show/prefill in the Checkout Session.
     *  - always - Customer explicitly agreed to save for future purchases (Full consent)
     *  - limited = Saved but with limited reuse rights (e.g. for the current subscription) (Restricted)
     *  - unspecified - Legacy / older saved methods where consent type wasn't recorded (Unknown)
     * If you are ever in doubt, I would either leave this as null, or provide a collection of just one allowRedisplayFilter
     * element that is set to "always".
 */
    public function __construct(
        private readonly ?bool $enableSavingOfPaymentMethod = true,
        private readonly ?bool $enablePaymentMethodRemoval = true,
        private readonly ?AllowRedisplayFiltersCollection $allowRedisplayFilters = null
    )
    {
        if ($enableSavingOfPaymentMethod === null && $enablePaymentMethodRemoval === null && $allowRedisplayFilters === null)
        {
            throw new ExceptionMissingRequiredParameter("You need to provide at least one value that is not null to SavedPaymentMethodOptions");
        }
    }


    public function toArray(): array
    {
        $arrayForm = [];

        if ($this->enableSavingOfPaymentMethod !== null)
        {
            $arrayForm['payment_method_save'] = $this->enableSavingOfPaymentMethod;
        }

        if ($this->enablePaymentMethodRemoval !== null)
        {
            $arrayForm['payment_method_remove'] = $this->enablePaymentMethodRemoval;
        }

        if ($this->allowRedisplayFilters !== null)
        {
            $arrayForm['allow_redisplay_filters'] = $this->allowRedisplayFilters->toStripeArrayForm();
        }

        return $arrayForm;
    }
}