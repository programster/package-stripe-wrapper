# An image for testing the package on a specific version of PHP. Used by testing/run-all-php-versions.sh.
# Build with e.g.: docker build --build-arg PHP_VERSION=8.2 -t stripewrapper-php8.2-test testing/php-versions
# The official image already has the curl, json and mbstring extensions that stripe/stripe-php needs.
ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-cli
