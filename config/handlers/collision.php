<?php

use NunoMaduro\Collision\Provider;

return [
    '$nmCollision' => [
        'instanceOf' => Provider::class,
        'call' => [['register']],
    ],
];
