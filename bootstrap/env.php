<?php

namespace Lot2k\Buildskip;

use Dotenv\Dotenv;
use Dotenv\Exception\ValidationException;

use function str_contains;

$dotenv = Dotenv::createImmutable(From::___->dir());
$dotenv->safeLoad();

try {
    $dotenv->required('BUILDSKIP_ENABLE')->allowedRegexValues('#^LICENSE_KEY$#m');
} catch (ValidationException $e) {
    if (str_contains($e->getMessage(), 'missing')) {
        show('missing-buildskip-enable');
        exit(Buildskip::NOT_GOOD);
    }

    show('invalid-license-key');
    exit(Buildskip::NOT_GOOD);
}

$dotenv->ifPresent('BUILDSKIP_TAG')->allowedRegexValues('#^$|\d+\.\d+\.\d+#');
