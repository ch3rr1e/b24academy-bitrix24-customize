<?php

use Bitrix\Main\Localization\Loc;

$tabs = [
    [
        'DIV' => 'general',
        'TAB' => Loc::getMessage('B24_ACADEMY.GENERAL_TAB'),
        'TITLE' => Loc::getMessage('B24_ACADEMY.GENERAL_TAB'),
    ],
];

$options = [
    'general' => [
        [
            'FACTS_IBLOCK_ID',
            Loc::getMessage('B24_ACADEMY.COMPANY_FACTS_IBLOCK_ID'),
            '',
            ['text'],
        ]
    ],
];

if (check_bitrix_sessid() && strlen($_POST['save']) > 0) {
    foreach ($options as $optionBlock) {
        __AdmSettingsSaveOptions($mid, $optionBlock);
    }
}

?>
<form method="POST" action="<?= $APPLICATION->GetCurPage(); ?>?mid=<?= $mid; ?>&lang=<?= LANGUAGE_ID; ?>">
    <?php
    $tabControl = new CAdminTabControl('tabControl', $tabs);
    $tabControl->Begin();
    foreach ($tabs as $tab) {
        $tabControl->BeginNextTab();
        __AdmSettingsDrawList($mid, $options[$tab['DIV']]);
    }
    $tabControl->Buttons(array('btnApply' => false, 'btnCancel' => false, 'btnSaveAndAdd' => false));
    echo bitrix_sessid_post();
    $tabControl->End();
    ?>
</form>
