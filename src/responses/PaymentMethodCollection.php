<?php

/*
 * A readonly collection of PaymentMethod response models, e.g. from a list method.
 */

namespace Programster\Stripe\Responses;

use Programster\Collections\AbstractReadonlyCollection;

/**
 * @extends AbstractReadonlyCollection<PaymentMethod>
 */
final readonly class PaymentMethodCollection extends AbstractReadonlyCollection
{
    public function __construct(PaymentMethod ...$elements)
    {
        parent::__construct(...$elements);
    }


    public function getType(): string { return PaymentMethod::class; }
}
