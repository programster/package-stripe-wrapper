<?php

/*
 * What every response model shares: the object's fields, as Stripe sent them, and the client that got them, for the
 * fetch...() methods that ask Stripe for related objects. The models are readonly, and can't be serialized, as the
 * client holds the secret key.
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Exceptions\ExceptionCustomerDeleted;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\StripeClient;

/**
 * @phpstan-consistent-constructor - every response model's constructor takes the same two parameters.
 */
abstract readonly class AbstractResponse
{
    protected ResponseFields $m_fields;
    protected StripeClient $m_client;
    private string $m_id;


    /**
     * @param ResponseFields $fields - the object's fields.
     * @param StripeClient $client - the client that got the object, for fetching related objects.
     * @throws ExceptionUnexpectedResponse - if the object has no ID.
     */
    protected function __construct(ResponseFields $fields, StripeClient $client)
    {
        $this->m_fields = $fields;
        $this->m_client = $client;
        $this->m_id = $fields->getString('id');
    }


    /**
     * Create the model from one of the Stripe SDK's objects, e.g. the object in a webhook event.
     *
     * @param \Stripe\StripeObject $object - the SDK's object, which must be of the type this model is for.
     *
     * @param StripeClient $client - the client to use for the model's fetch...() methods.
     *
     * @return static
     *
     * @throws ExceptionUnexpectedResponse - if the object is of a different type, or is missing a field the model
     * needs.
     *
     * @throws ExceptionCustomerDeleted - if it's a customer that's been deleted, as Stripe then only sends its ID.
     */
    public static function createFromStripeObject(\Stripe\StripeObject $object, StripeClient $client): static
    {
        $values = $object->toArray();
        $type = $values['object'] ?? null;
        $id = $values['id'] ?? 'with no ID';
        $expectedType = static::getObjectType();

        if ($type !== $expectedType)
        {
            $actualType = is_string($type) ? $type : get_debug_type($type);

            throw new ExceptionUnexpectedResponse(
                "A " . static::class . " can only be created from a Stripe {$expectedType}, not a {$actualType}."
            );
        }

        $idDescription = is_string($id) ? $id : 'with an invalid ID';
        return new static(new ResponseFields($values, "{$expectedType} {$idDescription}"), $client);
    }


    /**
     * Create the model from an object's fields, e.g. ones nested in another object, such as a subscription's items.
     * @param ResponseFields $fields - the object's fields.
     * @param StripeClient $client - the client to use for the model's fetch...() methods.
     * @return static
     * @throws ExceptionUnexpectedResponse - if a field the model needs is missing, or of an unexpected type.
     * @internal - for the response models and the client. Use createFromStripeObject() instead.
     */
    public static function createFromFields(ResponseFields $fields, StripeClient $client): static
    {
        return new static($fields, $client);
    }


    /**
     * Get a copy of the SDK's object, as Stripe sent it, for any field the model doesn't have. It's a fresh copy each
     * time, so changing it can't change the model. Each model returns the SDK's class for its object, e.g.
     * \Stripe\Customer.
     * @return \Stripe\StripeObject
     */
    abstract public function getStripeObject(): \Stripe\StripeObject;


    /**
     * The models can't be serialized, as they hold the client, which holds the secret key.
     * @return array<string, mixed>
     * @throws \LogicException - always.
     */
    public function __serialize(): array
    {
        throw new \LogicException(
            static::class . " can't be serialized, as it holds the StripeClient, and so the secret key. Store its " .
            "ID instead, and retrieve it again when needed."
        );
    }


    /**
     * Rebuild the SDK's object from the fields Stripe sent, as a fresh copy.
     * @template TStripeObject of \Stripe\StripeObject
     * @param class-string<TStripeObject> $class - the SDK's class for the object, e.g. \Stripe\Customer::class.
     * @return TStripeObject
     * @throws \LogicException - if the SDK rebuilds it as a different class, which would be a bug.
     */
    protected function rebuildStripeObject(string $class): \Stripe\StripeObject
    {
        $object = \Stripe\Util\Util::convertToStripeObject($this->m_fields->toArray(), []);

        if (!($object instanceof $class))
        {
            throw new \LogicException("The SDK rebuilt the object for " . static::class . " as the wrong class.");
        }

        return $object;
    }


    /**
     * Get the type of Stripe object the model is for, as Stripe names it in the object's "object" field, e.g.
     * "customer" or "checkout.session".
     * @return string
     */
    abstract protected static function getObjectType(): string;


    # Accessors
    public function getId(): string { return $this->m_id; }
}
