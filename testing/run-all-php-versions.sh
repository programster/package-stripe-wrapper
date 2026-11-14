#!/bin/sh
# Runs lint and the smoke tests (testing/main.php) on every supported version of PHP, each in its own Docker
# container. This catches code that only works on newer versions of PHP than the minimum this package supports.
# The tests never contact Stripe: every request is caught by a fake HTTP client, which records what would have been
# sent, so no API key or network access is needed.
#
# Usage:
#   testing/run-all-php-versions.sh                # test every supported version
#   testing/run-all-php-versions.sh 8.3 8.5        # test only these versions
#   testing/run-all-php-versions.sh --rebuild      # rebuild the Docker images first (e.g. for the latest patch release)
#
# Requires Docker, and "composer install" to have been run, as the tests use the vendor/ directory.
# Exits with 0 if every version passed, or 1 if any failed.

SUPPORTED_VERSIONS="8.2 8.3 8.4 8.5"

scriptDir=$(cd "$(dirname "$0")" && pwd)
repoDir=$(dirname "$scriptDir")
rebuild=0
versions=""

for arg in "$@"
do
    if [ "$arg" = "--rebuild" ]
    then
        rebuild=1
    else
        versions="$versions $arg"
    fi
done

if [ -z "$versions" ]
then
    versions=$SUPPORTED_VERSIONS
fi

if [ ! -f "$repoDir/vendor/autoload.php" ]
then
    echo "vendor/autoload.php is missing. Please run \"composer install\" first."
    exit 1
fi

failedVersions=""

for version in $versions
do
    image="stripewrapper-php${version}-test"
    echo
    echo "################ PHP ${version}"

    if [ "$rebuild" -eq 1 ] || ! docker image inspect "$image" > /dev/null 2>&1
    then
        echo "building ${image}..."

        if ! docker build -q --pull --build-arg PHP_VERSION="$version" -t "$image" "$scriptDir/php-versions" > /dev/null
        then
            echo "FAILED to build the image for PHP ${version}"
            failedVersions="$failedVersions $version"
            continue
        fi
    fi

    # --network none proves the tests can't reach Stripe, even by mistake.
    if ! docker run --rm --network none -v "$repoDir":/src:ro "$image" sh /src/testing/php-versions/run-in-container.sh
    then
        failedVersions="$failedVersions $version"
    fi
done

echo
if [ -z "$failedVersions" ]
then
    echo "All passed:" $versions
    exit 0
else
    echo "FAILED on PHP:" $failedVersions
    exit 1
fi
