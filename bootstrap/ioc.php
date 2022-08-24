<?php

namespace Lot2k\Buildskip;

use Dice\Dice;

use function array_merge;

return (new Dice())
    ->addRules(
        array_merge(
            [],
            require In::CONFIG->dir('core.php'),
        )
    );
