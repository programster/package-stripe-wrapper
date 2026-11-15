<?php

/*
 * Runs the live tests in testing/live-tests/, which send real requests to Stripe's test mode, to check that Stripe
 * accepts what this package sends (which the offline smoke tests in testing/main.php can't). It needs a test-mode key
 * in testing/settings.php (copy testing/settings.php.tmpl), and refuses to run with a live key. Everything the tests
 * create is removed afterwards, as far as Stripe allows (see LiveFixtures::cleanUp()).
 * Run with: php -d zend.assertions=1 testing/main-live.php
 * Exits with 0 if everything passed, or 1 if anything failed.
 */

require_once(__DIR__ . '/../vendor/autoload.php');

use Programster\Stripe\Testing\LiveFixtures;
use Programster\Stripe\Testing\SmokeTest;


if (ini_get('zend.assertions') !== '1' && @ini_set('zend.assertions', '1') === false)
{
    echo "zend.assertions must be 1 for the tests to check anything. Run: php -d zend.assertions=1 " . __FILE__ . "\n";
    exit(1);
}

ini_set('assert.exception', '1');
$settingsPath = __DIR__ . '/settings.php';

if (!is_file($settingsPath))
{
    echo "testing/settings.php is missing. Copy testing/settings.php.tmpl to it, and fill in a test-mode key.\n";
    exit(1);
}

require_once($settingsPath);

# Only ever run against test mode. A live key would create real customers and subscriptions, and could charge cards.
$isTestKey = (str_starts_with(STRIPE_SECRET_KEY, 'sk_test_') || str_starts_with(STRIPE_SECRET_KEY, 'rk_test_'));

if (!$isTestKey)
{
    echo "STRIPE_SECRET_KEY in testing/settings.php must be a test-mode key (sk_test_...). Refusing to run.\n";
    exit(1);
}

$smoke = new SmokeTest();

try
{
    $fixtures = new LiveFixtures(STRIPE_SECRET_KEY);
}
catch (\Throwable $e)
{
    echo "Couldn't create the test fixtures in Stripe, so no tests were run: " . $e->getMessage() . "\n";
    exit(1);
}

# The files run in this order, as later ones use what earlier ones created.
$testFiles = ['CustomersTest.php', 'SubscriptionsTest.php', 'CheckoutTest.php'];

try
{
    foreach ($testFiles as $testFile)
    {
        require __DIR__ . '/live-tests/' . $testFile;
    }
}
finally
{
    $cleanUpProblems = $fixtures->cleanUp();
}

# The accessors are one line, with no docblock, so these say what their lists hold, as their properties do.
/** @var list<string> $failures */
$failures = $smoke->getFailures();
printf("PHP %s, live: %d passed, %d failed\n", PHP_VERSION, $smoke->getNumPassed(), count($failures));

foreach ($failures as $failure)
{
    echo "  FAIL {$failure}\n";
}

/** @var list<string> $skippedTests */
$skippedTests = $smoke->getSkipped();

foreach ($skippedTests as $skipped)
{
    echo "  SKIPPED {$skipped}\n";
}

foreach ($cleanUpProblems as $problem)
{
    echo "  CLEAN-UP {$problem}\n";
}

exit(count($failures) === 0 ? 0 : 1);
