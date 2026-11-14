<?php

/*
 * The types of payment method, e.g. card, that a customer can pay with.
 * https://docs.stripe.com/api/payment_methods/object#payment_method_object-type
 */

namespace Programster\Stripe\Enums;


enum PaymentMethodType: string
{
    // Pre-authorized debit payments are used to debit Canadian bank accounts through the Automated Clearing Settlement
    // System (ACSS).
    case ACSS_DEBIT = "acss_debit";

    // Affirm is a buy now, pay later payment method in the US.
    case AFFIRM = "affirm";

    // Afterpay / Clearpay is a buy now, pay later payment method used in Australia, Canada, France, New Zealand,
    // Spain, the UK, and the US.
    case AFTERPAY_CLEARPAY = "afterpay_clearpay";

    // Alipay is a digital wallet payment method used in China.
    case ALIPAY = "alipay";

    // Alma is a Buy Now, Pay Later payment method that lets customers pay in 2, 3, or 4 installments.
    case ALMA = "alma";

    // Amazon Pay is a Wallet payment method that lets hundreds of millions of Amazon customers pay their way, every
    // day.
    case AMAZON_PAY = "amazon_pay";

    // BECS Direct Debit is used to debit Australian bank accounts through the Bulk Electronic Clearing System (BECS).
    case AU_BECS_DEBIT = "au_becs_debit";

    // Bacs Direct Debit is used to debit UK bank accounts.
    case BACS_DEBIT = "bacs_debit";

    // Bancontact is a bank redirect payment method used in Belgium.
    case BANCONTACT = "bancontact";

    // BLIK is a single-use payment method common in Poland.
    case BLIK = "blik";

    // Boleto is a voucher-based payment method used in Brazil.
    case BOLETO = "boleto";

    // Card payments are supported through many networks, card brands, and select Link funding sources.
    case CARD = "card";

    // Stripe Terminal is used to collect in-person card payments.
    case CARD_PRESENT = "card_present";

    // Cash App Pay enables customers to frictionlessly authenticate payments in the Cash App using their stored balance
    // or linked card.
    case CASHAPP = "cashapp";

    // Uses a customer’s cash balance for the payment.
    case CUSTOMER_BALANCE = "customer_balance";

    // EPS is an Austria-based bank redirect payment method.
    case EPS = "eps";

    // FPX is a Malaysia-based bank redirect payment method.
    case FPX = "fpx";

    // giropay is a German bank redirect payment method.
    case GIROPAY = "giropay";

    // GrabPay is a digital wallet payment method used in Southeast Asia.
    case GRABPAY = "grabpay";

    // iDEAL is a Netherlands-based bank redirect payment method.
    case IDEAL = "ideal";

    // Stripe Terminal accepts Interac debit cards for in-person payments in Canada.
    case INTERAC_PRESENT = "interac_present";

    // Kakao Pay is a digital wallet payment method used in South Korea.
    case KAKAO_PAY = "kakao_pay";

    // Klarna is a global buy now, pay later payment method.
    case KLARNA = "klarna";

    // Konbini is a cash-based voucher payment method used in Japan.
    case KONBINI = "konbini";

    // Korean cards enables customers to accept local credit and debit cards in South Korea.
    case KR_CARD = "kr_card";

    // Link allows customers to pay with their saved payment details.
    case LINK = "link";

    // MobilePay is a Nordic card-passthrough wallet payment method where customers authorize the payment in the
    // MobilePay application.
    case MOBILEPAY = "mobilepay";

    // Multibanco is a voucher payment method
    case MULTIBANCO = "multibanco";

    // Naver Pay is a digital wallet payment method used in South Korea.
    case NAVER_PAY = "naver_pay";

    // OXXO is a cash-based voucher payment method used in Mexico.
    case OXXO = "oxxo";

    // Przelewy24 is a bank redirect payment method used in Poland.
    case P24 = "p24";

    // Pay By Bank is an open banking payment method in the UK.
    case PAY_BY_BANK = "pay_by_bank";

    // PAYCO is a digital wallet payment method used in South Korea.
    case PAYCO = "payco";

    // PayNow is a QR code payment method used in Singapore.
    case PAYNOW = "paynow";

    // PayPal is an online wallet and redirect payment method commonly used in Europe.
    case PAYPAL = "paypal";

    // Pix is an instant bank transfer payment method in Brazil.
    case PIX = "pix";

    // PromptPay is an instant funds transfer service popular in Thailand.
    case PROMPTPAY = "promptpay";

    // Revolut Pay is a digital wallet payment method used in the United Kingdom.
    case REVOLUT_PAY = "revolut_pay";

    // Samsung Pay is a digital wallet payment method used in South Korea.
    case SAMSUNG_PAY = "samsung_pay";

    // SEPA Direct Debit is used to debit bank accounts within the Single Euro Payments Area (SEPA) region.
    case SEPA_DEBIT = "sepa_debit";

    // Sofort is a bank redirect payment method used in Europe.
    case SOFORT = "sofort";

    // Swish is a Swedish wallet payment method where customers authorize the payment in the Swish application.
    case SWISH = "swish";

    // TWINT is a payment method.
    case TWINT = "twint";

    // ACH Direct Debit is used to debit US bank accounts through the Automated Clearing House (ACH) payments system.
    case US_BANK_ACCOUNT = "us_bank_account";

    // WeChat Pay is a digital wallet payment method based in China.
    case WECHAT_PAY = "wechat_pay";

    // Zip is a Buy now, pay later Payment Method
    case ZIP = "zip";


    /**
     * Get all of the payment method types.
     * @deprecated - use PaymentMethodType::cases() instead, which this now just returns.
     * @return PaymentMethodType[]
     */
    public static function getAll() : array
    {
        return self::cases();
    }
}
