<?php

/*
 * Runs the smoke tests in testing/tests/, which call every public method of StripeClient and check what would be sent
 * to Stripe, and build the models and collections and check what they turn into. Nothing is sent to Stripe, as every
 * request is caught by FakeStripeHttpClient. Any test that fails, throws an error, or raises a warning/deprecation is
 * reported. This is used by run-all-php-versions.sh to check the package works on every supported version of PHP,
 * but it can also be run directly: php -d zend.assertions=1 testing/main.php
 * Exits with 0 if everything passed, or 1 if anything failed.
 */

require_once(__DIR__ . '/../vendor/autoload.php');

use Programster\Stripe\Testing\FakeStripeHttpClient;
use Programster\Stripe\Testing\SmokeTest;
use Stripe\ApiRequestor;


# The tests check things with assert(), which does nothing when zend.assertions is 0 or -1, so every test would pass
# without checking anything. It can be switched from 0 to 1 here, but -1 (the usual production setting) can only be
# changed in php.ini or on the command line.
if (ini_get('zend.assertions') !== '1' && @ini_set('zend.assertions', '1') === false)
{
    echo "zend.assertions must be 1 for the tests to check anything. Run: php -d zend.assertions=1 " . __FILE__ . "\n";
    exit(1);
}

ini_set('assert.exception', '1');

$smoke = new SmokeTest();
$fakeHttpClient = new FakeStripeHttpClient();
ApiRequestor::setHttpClient($fakeHttpClient);

$testFiles = glob(__DIR__ . '/tests/*Test.php');

if ($testFiles === false)
{
    echo "Couldn't list the test files in " . __DIR__ . "/tests.\n";
    exit(1);
}

sort($testFiles);

foreach ($testFiles as $testFile)
{
    require $testFile;
}

# The accessors are one line, with no docblock, so these say what their lists hold, as their properties do.
/** @var list<string> $failures */
$failures = $smoke->getFailures();

# With STRIPE_WRAPPER_TEST_ONLY, a name that matches nothing would otherwise pass without checking anything.
$only = getenv("STRIPE_WRAPPER_TEST_ONLY");

if ($only !== false && $only !== "" && $smoke->getNumPassed() === 0 && count($failures) === 0)
{
    $failures[] = "STRIPE_WRAPPER_TEST_ONLY: no test's name contains \"{$only}\", so nothing was run.";
}

printf("PHP %s: %d passed, %d failed\n", PHP_VERSION, $smoke->getNumPassed(), count($failures));

foreach ($failures as $failure)
{
    echo "  FAIL {$failure}\n";
}

exit(count($failures) === 0 ? 0 : 1);
