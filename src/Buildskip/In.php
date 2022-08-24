<?php

namespace Lot2k\Buildskip;

use function str_replace;
use function dirname;

use const DIRECTORY_SEPARATOR;

enum In: string
{
    case ___ = '___';
    case ART = 'art';
    case BOOTSTRAP = 'bootstrap';
    case CONFIG = 'config';

    public function dir(string $userSuffix = ''): string
    {
        $suffix = $this->value === '___'
            ? ''
            : (str_replace('___', DIRECTORY_SEPARATOR, $this->value) . DIRECTORY_SEPARATOR);

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $suffix
            . $userSuffix;
    }

    public function asDirectoryInLocalRoot(): string
    {
        return $this->dir();
    }

    public function asDir(): string
    {
        return $this->dir();
    }

    public function asDirectory(): string
    {
        return $this->dir();
    }

    public function directory(): string
    {
        return $this->dir();
    }
}
