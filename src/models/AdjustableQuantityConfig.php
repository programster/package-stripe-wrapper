<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Interfaces\Arrayable;

readonly class AdjustableQuantityConfig implements Arrayable
{
    public function __construct(private int $minimum, private int $maximum)
    {

    }

    public function toArray(): array
    {
        return [
            'enabled' => true,
            'minimum' => $this->minimum,
            'maximum' => $this->maximum,
        ];
    }
}