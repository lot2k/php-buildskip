<?php

namespace Lot2k\Buildskip;

use A6a\FromDir\AsDirectoryInPackageRoot;
use A6a\FromDir\NamesDirectoryInPackageRoot;

enum From implements NamesDirectoryInPackageRoot
{
    use AsDirectoryInPackageRoot;

    case ___;
}
