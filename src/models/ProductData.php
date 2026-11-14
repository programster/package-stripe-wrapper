<?php

/*
 * The data for an inline product, for when a price is created along with the checkout session.
 * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price_data-product_data
 */

namespace Programster\Stripe\Models;

use Programster\Stripe\Collections\Metadata;
use Programster\Stripe\Collections\StringCollection;
use Programster\Stripe\Exceptions\ExceptionInvalidValue;
use Programster\Stripe\Interfaces\Arrayable;


readonly class ProductData implements Arrayable
{
    private string $m_name;
    private ?string $m_description;
    private ?StringCollection $m_images;
    private ?Metadata $m_metadata;
    private ?string $m_taxCodeId;


    /**
     * @param string $name - the name of the product, which is shown to the customer.
     *
     * @param string|null $description - a description of the product, which is shown to the customer.
     *
     * @param StringCollection|null $images - A list of up to 8 URLs of images for this product, meant to be
     * displayable to the customer.
     *
     * @param Metadata|null $metadata - key-value pairs to attach to the product.
     *
     * @param string|null $taxCodeId - the ID of the product's tax code.
     * https://docs.stripe.com/api/checkout/sessions/create#create_checkout_session-line_items-price_data-product_data-tax_code
     *
     * @throws ExceptionInvalidValue - if there are more than 8 images, or one of them isn't a valid URL.
     */
    public function __construct(
        string $name,
        ?string $description = null,
        ?StringCollection $images = null,
        ?Metadata $metadata = null,
        ?string $taxCodeId = null,
    )
    {
        if ($images !== null)
        {
            if (count($images) > 8)
            {
                throw new ExceptionInvalidValue("You can only have up to 8 images for your product.");
            }

            foreach ($images as $image)
            {
                if (filter_var($image, FILTER_VALIDATE_URL) === false)
                {
                    throw new ExceptionInvalidValue("{$image} is not a valid URL for an image.");
                }
            }
        }

        $this->m_name = $name;
        $this->m_description = $description;
        $this->m_images = ($images !== null) ? clone $images : null;
        $this->m_metadata = ($metadata !== null) ? clone $metadata : null;
        $this->m_taxCodeId = $taxCodeId;
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $arrayForm = ['name' => $this->m_name];

        if ($this->m_description !== null)
        {
            $arrayForm['description'] = $this->m_description;
        }

        if ($this->m_images !== null)
        {
            $arrayForm['images'] = $this->m_images->toArray();
        }

        if ($this->m_metadata !== null)
        {
            $arrayForm['metadata'] = $this->m_metadata->toStripeArrayForm();
        }

        if ($this->m_taxCodeId !== null)
        {
            $arrayForm['tax_code'] = $this->m_taxCodeId;
        }

        return $arrayForm;
    }
}
