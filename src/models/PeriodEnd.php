<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Interfaces\Arrayable;
use Stripe\InvoiceRenderingTemplate;

readonly class PeriodEnd implements Arrayable
{

    private function __construct(
        private readonly string $type,
        private readonly ?int $timestamp = null,
    )
    {

    }


    public static function createMinItemPeriodEnd()
    {
        return new PeriodEnd('min_item_period_end');
    }


    public static function createTimestamp(int $timestamp)
    {
        return new PeriodEnd('timestamp', $timestamp);
    }


    public function toArray(): array
    {
        $arrayForm = [
            'type' => $this->type
        ];

        if ($this->timestamp !== null)
        {
            $arrayForm['timestamp'] = $this->timestamp;
        }

        return $arrayForm;
    }
}