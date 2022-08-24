<?php

namespace Lot2k\Buildskip;

use Dice\Dice;

(new Dice())
    ->addRules(require In::CONFIG->dir('handlers/collision.php'))
    ->create('$nmCollision');
