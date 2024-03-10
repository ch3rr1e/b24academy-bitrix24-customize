<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\Path;

CopyDirFiles(
    Path::combine(__DIR__, '/../files/activities/'),
    Path::convertRelativeToAbsolute('/local/activities/'),
    true,
    true
);

Option::set('b24.academy', 'VERSION', 2024_03_04_14_00_00);