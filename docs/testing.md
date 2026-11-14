# Testing

- [The offline tests](#the-offline-tests)
- [The live tests, against Stripe's test mode](#the-live-tests-against-stripes-test-mode)
- [PHPStan](#phpstan)
- [The documentation's examples](#the-documentations-examples)

The package is tested with smoke tests (in `testing/`), rather than PHPUnit, so there's no test framework to install
or keep working on every version of PHP. A test fails if it throws, fails an `assert()`, or raises any warning, notice
or deprecation.

## The offline tests
These call every public method of `StripeClient`, and build the models and collections, checking exactly what would be
sent to Stripe. They never contact Stripe: every request is caught by a fake HTTP client
(`testing/FakeStripeHttpClient.php`), so no API key or network access is needed.

To run them, and PHPStan, on PHP 8.2, 8.3, 8.4 and 8.5, each in its own Docker container, with networking switched
off:

```bash
composer install
testing/run-all-php-versions.sh
```

- To test only some versions, pass them, e.g. `testing/run-all-php-versions.sh 8.2 8.5`.
- To rebuild the Docker images first, e.g. to pick up the latest patch releases, pass `--rebuild`.
- To run them once on your own PHP instead: `php -d zend.assertions=1 testing/main.php`.

The GitHub workflow runs the same script, and writes the results as annotations (with `testing/github-annotations.sh`),
which can be read on the workflow's page without logging in to GitHub.

## The live tests, against Stripe's test mode
The offline tests can only check what the package sends, not that Stripe accepts it. The live tests send real requests
to Stripe's test mode, to check that too. They refuse to run with anything other than a test-mode key, so they can't
create real customers or charge real cards, and they aren't run by the GitHub workflow, as they need a key.

1. Copy `testing/settings.php.tmpl` to `testing/settings.php` (which git ignores), and fill in your test-mode secret
   key, from https://dashboard.stripe.com/test/apikeys.
2. Optionally, turn on the extra tests for Stripe Tax, terms of service consent and Stripe Connect, once they're set
   up in your test-mode account, as described in the template.
3. Run them: `php -d zend.assertions=1 testing/main-live.php`

Everything the tests create is cleaned up afterwards, as far as Stripe allows. Subscription customers are on a test
clock, which is deleted with everything attached to it. Other customers and the coupon are deleted, and the tax rate
and product are archived. Products that checkout sessions create from inline product data can't be removed through
the API, so they stay in test mode, marked with `created_by: programster-stripe-wrapper-live-tests` in their metadata.

## PHPStan
```bash
vendor/bin/phpstan
```

It runs at level 6, the highest that's clean before 1.0.0, set in `phpstan.neon.dist`. It loads
`testing/settings.php.tmpl`, so it doesn't need a real `testing/settings.php`.

## The documentation's examples
`testing/tests/DocsTest.php` runs the PHP examples in the README and every page in `docs/`, so the documentation can't
drift from what the code does. Each document's examples run in order, as one script, in a separate PHP process, with a
`use` statement for every class in the package added, and every request caught by the fake HTTP client, so nothing is
sent to Stripe. An example whose first line is `// Throws SomeException` must throw that exception. See
`testing/DocsExampleRunner.php`.
