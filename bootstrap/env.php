<?php

use Dotenv\Dotenv;
use Dotenv\Exception\ValidationException;
use Lot2k\Buildskip\From;

$dotenv = Dotenv::createImmutable(From::___->dir());
$dotenv->safeLoad();

try {
    $dotenv->required('BUILDSKIP_ENABLE')->allowedRegexValues('#LICENSE_KEY#');
} catch (ValidationException $e) {
    if (str_contains($e->getMessage(), 'missing')) {
        echo "error: you must have BUILDSKIP_ENABLE in .env to use buildskip";
        exit(1);
    }

    echo "error: invalid LICENSE_KEY. Check the README.md.";
    exit(1);
}

$dotenv->ifPresent('BUILDSKIP_TAG')->allowedRegexValues('#^$|\d+\.\d+\.\d+#');
