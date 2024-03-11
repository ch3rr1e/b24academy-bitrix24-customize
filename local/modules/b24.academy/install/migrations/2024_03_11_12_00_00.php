<?php

use B24\Academy\Crm\Deal\Observer;
use B24\Academy\Crm\Kanban\DealEntity;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;


$eventManager = EventManager::getInstance();
$eventManager->registerEventHandler(
    'crm',
    'OnBeforeCrmDealUpdate',
    'b24.academy',
    Observer::class,
    'handleOnBeforeCrmDealUpdate',
);
$eventManager->registerEventHandler(
    'main',
    'OnProlog',
    'b24.academy',
    DealEntity::class,
    'registerService',
);

Option::set('b24.academy', 'VERSION', 2024_03_11_12_00_00);
