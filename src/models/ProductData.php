<?php

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Exceptions\ExceptionValueTooLong;
use Programster\Stripe\Interfaces\Arrayable;

readonly class ProductData implements Arrayable
{
    /**
     * @param string $name
     * @param string|null $description
     * @param StringCollection|null $images - A list of up to 8 URLs of images for this product, meant to be displayable to the customer.
     * @param Metadata|null $metadata
     * @param string|null $taxCodeId - https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price_data-product_data-tax_code
     */
    public function __construct(
        private string $name,
        private ?string $description = null,
        private ?StringCollection $images = null,
        private ?Metadata $metadata = null,
        private ?string $taxCodeId = null,
    )
    {
        if ($this->images !== null && count($this->images) > 8)
        {
            throw new ExceptionValueTooLong("You can only have up to 8 images for your product.");
        }

        if ($this->images !== null)
        {
            foreach ($this->images as $image)
            {
                if (filter_var($image, FILTER_VALIDATE_URL) === false)
                {
                    throw new ExceptionInvalidValue("$image is not a valid URL for an image.");
                }
            }
        }
    }

    public function toArray(): array
    {
        $arrayForm = [
            'name' => $this->name,
        ];

        if ($this->description !== null)
        {
            $arrayForm['description'] = $this->description;
        }

        if ($this->images !== null)
        {
            $arrayForm['images'] = $this->images->toArray();
        }

        if ($this->metadata !== null)
        {
            $arrayForm['metadata'] = $this->metadata->toStripeArrayForm();
        }

        if ($this->taxCodeId !== null)
        {
            $arrayForm['taxCodeId'] = $this->taxCodeId;
        }

        return $arrayForm;
    }
}