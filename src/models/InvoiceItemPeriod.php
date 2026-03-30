<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Interfaces\Arrayable;
use Stripe\InvoiceRenderingTemplate;

readonly class InvoiceItemPeriod implements Arrayable
{

    public function __construct(
        private PeriodStart $end,
        private PeriodEnd $start,
    )
    {

    }


    public function toArray(): array
    {
        return [
            'end' => $this->end->toArray(),
            'start' => $this->start->toArray(),
        ];
    }
}