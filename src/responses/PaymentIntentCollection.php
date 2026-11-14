<?php

/*
 * A readonly collection of PaymentIntent response models, e.g. from a list method.
 */

namespace Programster\Stripe\Responses;

use Programster\Collections\AbstractReadonlyCollection;

/**
 * @extends AbstractReadonlyCollection<PaymentIntent>
 */
final readonly class PaymentIntentCollection extends AbstractReadonlyCollection
{
    public function __construct(PaymentIntent ...$elements)
    {
        parent::__construct(...$elements);
    }


    public function getType(): string { return PaymentIntent::class; }
}
