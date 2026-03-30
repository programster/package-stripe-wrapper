<?php


namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\CustomFieldCollection;
use Programster\Stripe\Collections\InvoiceCustomFieldCollection;
use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Interfaces\Arrayable;
use Stripe\InvoiceRenderingTemplate;

readonly class PeriodStart implements Arrayable
{
    private function __construct(
        private readonly string $type,
        private readonly ?int $timestamp = null,
    )
    {

    }


    public static function createMaxItemPeriodStart()
    {
        return new PeriodStart('max_item_period_start');
    }


    public static function createNow()
    {
        return new PeriodStart('now');
    }


    public static function createTimestamp(int $timestamp)
    {
        return new PeriodStart('timestamp', $timestamp);
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