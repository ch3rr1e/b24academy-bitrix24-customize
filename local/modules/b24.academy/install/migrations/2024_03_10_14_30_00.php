<?php

use B24\Academy\Task\ActivityWatcher;
use Bitrix\Main\Config\Option;

CAgent::AddAgent(
    ActivityWatcher::class . '::runAgent();',
    'b24.academy',
    'Y',
    60,
    '',
    'Y',
    \Bitrix\Main\Type\DateTime::createFromPhp(new DateTime('tomorrow 12am'))
);

Option::set('b24.academy', 'VERSION', 2024_03_10_14_30_00);
