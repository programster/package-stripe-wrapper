<?php

/*
 * The things the live tests need to already exist in Stripe (a product, a coupon, a tax rate and a test clock),
 * created straight through the stripe/stripe-php SDK, rather than this package, so a bug in the package can't stop
 * them being made. It also keeps track of everything the tests create, so cleanUp() can remove it afterwards.
 */

namespace Programster\Stripe\Testing;

use Stripe\StripeClient;


final class LiveFixtures
{
    # Put in the metadata of everything the tests create, so it can be recognised in the dashboard.
    public const METADATA = ['created_by' => 'programster-stripe-wrapper-live-tests'];

    private StripeClient $m_sdk;
    private string $m_productId;
    private string $m_couponId;
    private string $m_taxRateId;
    private string $m_testClockId;
    /** @var list<string> */
    private array $m_customerIds = [];

    /** @var list<string> */
    private array $m_checkoutSessionIds = [];


    /**
     * Create the fixtures in Stripe's test mode.
     * @param string $secretKey - the test-mode secret key.
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function __construct(#[\SensitiveParameter] string $secretKey)
    {
        $this->m_sdk = new StripeClient($secretKey);
        $product = $this->m_sdk->products->create(['name' => 'Live test product', 'metadata' => self::METADATA]);
        $this->m_productId = $product->id;

        $coupon = $this->m_sdk->coupons->create(
            ['percent_off' => 10, 'duration' => 'once', 'name' => 'Live test 10%', 'metadata' => self::METADATA]
        );

        $this->m_couponId = $coupon->id;

        $taxRate = $this->m_sdk->taxRates->create([
            'display_name' => 'VAT',
            'percentage' => 20,
            'inclusive' => false,
            'country' => 'GB',
            'metadata' => self::METADATA,
        ]);

        $this->m_taxRateId = $taxRate->id;

        # Subscriptions are made for customers on a test clock, so that deleting the clock afterwards deletes them,
        # along with their subscriptions and invoices.
        $testClock = $this->m_sdk->testHelpers->testClocks->create(
            ['frozen_time' => time(), 'name' => 'programster/stripe-wrapper live tests']
        );

        $this->m_testClockId = $testClock->id;
    }


    /**
     * Remember a customer the tests created (without a test clock), so that cleanUp() deletes it.
     * @param string $customerId - the customer's ID.
     * @return void
     */
    public function trackCustomer(string $customerId): void
    {
        $this->m_customerIds[] = $customerId;
    }


    /**
     * Remember a checkout session the tests created, so that cleanUp() expires it.
     * @param string $checkoutSessionId - the session's ID.
     * @return void
     */
    public function trackCheckoutSession(string $checkoutSessionId): void
    {
        $this->m_checkoutSessionIds[] = $checkoutSessionId;
    }


    /**
     * Remove what the tests created, as far as Stripe allows. Sessions are expired, and the test clock is deleted,
     * which deletes its customers, subscriptions and invoices. Other customers and the coupon are deleted. The tax rate
     * and product can't be deleted once used, so they are archived instead. Products that checkout sessions created
     * from inline product data can't be found to remove, so they stay, marked with METADATA.
     * @return array<int, string> - a description of anything that couldn't be cleaned up, for reporting.
     */
    public function cleanUp(): array
    {
        $problems = [];

        $steps = [];

        foreach ($this->m_checkoutSessionIds as $sessionId)
        {
            $steps["expire checkout session {$sessionId}"] = function () use ($sessionId): void {
                $this->m_sdk->checkout->sessions->expire($sessionId);
            };
        }

        foreach ($this->m_customerIds as $customerId)
        {
            $steps["delete customer {$customerId}"] = function () use ($customerId): void {
                $this->m_sdk->customers->delete($customerId);
            };
        }

        $steps["delete test clock {$this->m_testClockId}"] = function (): void {
            $this->m_sdk->testHelpers->testClocks->delete($this->m_testClockId);
        };

        $steps["delete coupon {$this->m_couponId}"] = function (): void {
            $this->m_sdk->coupons->delete($this->m_couponId);
        };

        $steps["archive tax rate {$this->m_taxRateId}"] = function (): void {
            $this->m_sdk->taxRates->update($this->m_taxRateId, ['active' => false]);
        };

        $steps["archive product {$this->m_productId}"] = function (): void {
            $this->m_sdk->products->update($this->m_productId, ['active' => false]);
        };

        foreach ($steps as $description => $step)
        {
            try
            {
                $step();
            }
            catch (\Throwable $e)
            {
                $problems[] = "couldn't {$description}: " . $e->getMessage();
            }
        }

        return $problems;
    }


    # Accessors
    public function getSdk(): StripeClient { return $this->m_sdk; }
    public function getProductId(): string { return $this->m_productId; }
    public function getCouponId(): string { return $this->m_couponId; }
    public function getTaxRateId(): string { return $this->m_taxRateId; }
    public function getTestClockId(): string { return $this->m_testClockId; }
}
