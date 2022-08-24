<?php

namespace Lot2k\Buildskip;

use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\render;
use function function_exists;
use function file_exists;
use function file_get_contents;
use function array_map;
use function array_keys;
use function str_replace;
use function array_values;

if (! function_exists('Lot2k\Buildskip\show')) {
    /** @param array<string, string> $vars */
    function show(string $source, array $vars = [], int $options = OutputInterface::OUTPUT_NORMAL): void
    {
        if (! file_exists(In::ART->dir("{$source}.html"))) {
            show('404', ['path' => "art/{$source}.html"]);

            return;
        }

        $search = array_map(static fn (string $term): string => "<?= \${$term} ?>", array_keys($vars));

        render(
            str_replace($search, array_values($vars), file_get_contents(In::ART->dir("{$source}.html"))),
            $options
        );
    }
}
