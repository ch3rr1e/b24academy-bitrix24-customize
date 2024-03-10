<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');

$iblockType = new CIBlockType();
$typeId = $iblockType->Add(array(
    'ID' => 'b24_academy_company_fact',
    'SECTIONS' => 'N',
    'IN_RSS' => 'N',
    'SORT' => 100,
    'LANG' => array(
        'ru' => array(
            'NAME' => 'Факты о компании',
            'ELEMENT_NAME' => 'Факты',
        ),
        'en' => array(
            'NAME' => 'Company facts',
            'ELEMENT_NAME' => 'Facts',
        ),
    ),
));

if (!empty($iblockType->LAST_ERROR)) {
    throw new RuntimeException($iblockType->LAST_ERROR);
}

$iblockManager = new CIBlock();
$ibId = $iblockManager->Add(array(
    'ACTIVE' => 'Y',
    'API_CODE' => 'companyFacts',
    'NAME' => 'Факты о компании',
    'IBLOCK_TYPE_ID' => $typeId,
    'LID' => 's1',
    'WORKFLOW' => 'N',
    'BIZPROC' => 'N',
    'VERSION' => 2,
    'CODE' => 'b24_academy_company_fact',
));

if (!empty($iblockManager->LAST_ERROR)) {
    throw new RuntimeException($iblockManager->LAST_ERROR);
}
Option::set('b24.academy', 'FACTS_IBLOCK_ID', $ibId);

CopyDirFiles(
    dirname(__DIR__) . '/files/components',
    $_SERVER['DOCUMENT_ROOT'] . '/local/components',
    true,
    true
);

$eventManager = EventManager::getInstance();
$eventManager->registerEventHandler(
    'main',
    'OnEpilog',
    'b24.academy',
    '\B24\Academy\UI\Menu\LeftMenuExtender',
    'handleOnEpilog'
);
Option::set('b24.academy', 'VERSION', 2023_09_01_10_00_00);