<?php

use Dice\Dice;

(new Dice())
    ->addRules(require __DIR__ . '/../config/handlers/collision.php')
    ->create('$nmCollision');
