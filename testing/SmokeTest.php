<?php

/*
 * A small test runner for the smoke tests in testing/tests/. Each test is a named callback, which fails if it throws
 * (including a failed assert()) or raises a warning, notice or deprecation.
 */

namespace Programster\Stripe\Testing;


final class SmokeTest
{
    private int $m_numPassed = 0;
    /** @var list<string> */
    private array $m_failures = [];

    /** @var list<string> */
    private array $m_warnings = [];

    # Tests that couldn't run here (e.g. ones needing a Stripe feature that isn't set up), so they can be reported.
    /** @var list<string> */
    private array $m_skipped = [];


    public function __construct()
    {
        error_reporting(-1);

        $recordWarning = function (int $errorNumber, string $message, string $file, int $line): bool {
            # Respect the @ operator, in case a dependency deliberately uses it.
            if (error_reporting() & $errorNumber)
            {
                $this->m_warnings[] = "[E{$errorNumber}] {$message} @ " . basename($file) . ":{$line}";
            }

            return true;
        };

        set_error_handler($recordWarning);
    }


    /**
     * Run one test, recording it as failed if it throws, or raises any warnings/deprecations.
     * @param string $label - the name of the test, shown if it fails.
     * @param callable $test - the test to run.
     * @return void
     */
    public function run(string $label, callable $test): void
    {
        $numWarningsBefore = count($this->m_warnings);
        $error = "";
        # STRIPE_WRAPPER_TEST_ONLY runs only the tests whose name contains it.
        $only = getenv("STRIPE_WRAPPER_TEST_ONLY");
        $isSelected = ($only === false || $only === "" || str_contains($label, $only));

        try
        {
            if ($isSelected)
            {
                $test();
            }
        }
        catch (\Throwable $e)
        {
            $error = get_class($e) . ": " . $e->getMessage() . " @ " . basename($e->getFile()) . ":" . $e->getLine();

            # When Stripe rejects a request, it names the parameter it didn't like, which pinpoints the mistake.
            if ($e instanceof \Stripe\Exception\InvalidRequestException && $e->getStripeParam() !== null)
            {
                $error .= " (Stripe rejected the parameter: " . $e->getStripeParam() . ")";
            }
        }

        $newWarnings = array_slice($this->m_warnings, $numWarningsBefore);

        if (!$isSelected)
        {
            # Not selected, so neither passed nor failed.
        }
        elseif ($error === "" && count($newWarnings) === 0)
        {
            $this->m_numPassed++;
        }
        else
        {
            $this->m_failures[] = trim("{$label}: {$error} " . implode(" | ", $newWarnings));
        }
    }


    /**
     * Record a test as skipped, rather than running it, so that it is reported, instead of looking like it passed.
     * @param string $label - the name of the test.
     * @param string $reason - why it can't run, e.g. what needs setting up for it to.
     * @return void
     */
    public function skip(string $label, string $reason): void
    {
        $this->m_skipped[] = "{$label} ({$reason})";
    }


    /**
     * Assert that a callback throws a specific type of exception.
     *
     * @param string $exceptionClass - the class of exception that should be thrown.
     *
     * @param callable $callback - the code that should throw it.
     *
     * @param string $messageContains - optionally, text the exception's message must contain, for when the same class
     * of exception can be thrown for different reasons.
     *
     * @return void
     *
     * @throws \Throwable - if the callback throws a different exception, it is rethrown.
     * @throws \Exception - if the callback doesn't throw at all, or the message doesn't contain $messageContains.
     */
    public static function expectException(
        string $exceptionClass,
        callable $callback,
        string $messageContains = ""
    ): void
    {
        $threwExpectedException = false;

        try
        {
            $callback();
        }
        catch (\Throwable $e)
        {
            if (!($e instanceof $exceptionClass))
            {
                throw $e;
            }

            if (!str_contains($e->getMessage(), $messageContains))
            {
                throw new \Exception(
                    "Expected the {$exceptionClass} message to contain \"{$messageContains}\", but it was: " .
                    $e->getMessage()
                );
            }

            $threwExpectedException = true;
        }

        if (!$threwExpectedException)
        {
            throw new \Exception("Expected {$exceptionClass} to be thrown, but nothing was thrown.");
        }
    }


    /**
     * Assert that two values are identical, showing both if they aren't, which a bare assert() doesn't.
     * @param mixed $expected - the value it should be.
     * @param mixed $actual - the value it is.
     * @param string $what - what is being compared, to show if it fails.
     * @return void
     */
    public static function assertSame(mixed $expected, mixed $actual, string $what): void
    {
        $describe = function (mixed $value): string {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        };

        assert($expected === $actual, "{$what}: expected {$describe($expected)}, got {$describe($actual)}");
    }


    /**
     * Get a value from inside nested arrays, such as a request's parameters, by the key at each level, failing the
     * test if any of them isn't there.
     *
     * @param array<mixed> $array - the outermost array.
     *
     * @param int|string ...$keys - the key at each level, outermost first.
     *
     * @return mixed - the value at the end of the keys.
     *
     * @throws \AssertionError - if a key isn't there, or the value before it isn't an array.
     */
    public static function getNested(array $array, int|string ...$keys): mixed
    {
        $value = $array;
        $path = '';

        foreach ($keys as $key)
        {
            $path .= "[{$key}]";

            if (!is_array($value) || !array_key_exists($key, $value))
            {
                throw new \AssertionError("There's nothing at {$path}.");
            }

            $value = $value[$key];
        }

        return $value;
    }


    # Accessors
    public function getNumPassed(): int { return $this->m_numPassed; }
    public function getFailures(): array { return $this->m_failures; }
    public function getSkipped(): array { return $this->m_skipped; }
}
