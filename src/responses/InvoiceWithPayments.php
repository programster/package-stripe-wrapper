<?php

/*
 * An invoice, along with every payment made against it, as Stripe sent them when asked to include the payments. Use
 * getInvoice() for the invoice's own details.
 */

namespace Programster\Stripe\Responses;

use Programster\Stripe\Exceptions\ExceptionIncompleteList;
use Programster\Stripe\Exceptions\ExceptionUnexpectedResponse;
use Programster\Stripe\Internal\ResponseFields;
use Programster\Stripe\StripeClient;


final readonly class InvoiceWithPayments
{
    private Invoice $m_invoice;
    private InvoicePaymentCollection $m_payments;


    /**
     * @param ResponseFields $fields - the invoice's fields, including its payments.
     * @param StripeClient $client - the client that got the invoice.
     * @throws ExceptionUnexpectedResponse - if the payments weren't included, or a field is of an unexpected type.
     * @throws ExceptionIncompleteList - if Stripe left some of the payments out.
     */
    private function __construct(ResponseFields $fields, StripeClient $client)
    {
        [$paymentFields, $hasMorePayments] = $fields->getList('payments');
        $payments = [];

        if ($hasMorePayments)
        {
            throw new ExceptionIncompleteList(
                "Invoice " . $fields->getString('id') . " has more payments than Stripe included in it. Use " .
                "Invoice::fetchPayments() to get them all."
            );
        }

        foreach ($paymentFields as $paymentField)
        {
            $payments[] = InvoicePayment::createFromFields($paymentField, $client);
        }

        $this->m_invoice = Invoice::createFromFields($fields, $client);
        $this->m_payments = new InvoicePaymentCollection(...$payments);
    }


    /**
     * Create one from the SDK's invoice, which must have been asked for with its payments (expand: payments).
     * @param \Stripe\StripeObject $object - the SDK's invoice, with its payments.
     * @param StripeClient $client - the client to use for the invoice's fetch...() methods.
     * @return InvoiceWithPayments
     * @throws ExceptionUnexpectedResponse - if it isn't an invoice, or its payments weren't included.
     * @throws ExceptionIncompleteList - if Stripe left some of the payments out.
     */
    public static function createFromStripeObject(
        \Stripe\StripeObject $object,
        StripeClient $client
    ): InvoiceWithPayments
    {
        $invoice = Invoice::createFromStripeObject($object, $client);
        $fields = ResponseFields::createFromArray($object->toArray(), "invoice {$invoice->getId()}");
        return new InvoiceWithPayments($fields, $client);
    }


    # Accessors
    public function getInvoice(): Invoice { return $this->m_invoice; }
    public function getPayments(): InvoicePaymentCollection { return $this->m_payments; }
}
