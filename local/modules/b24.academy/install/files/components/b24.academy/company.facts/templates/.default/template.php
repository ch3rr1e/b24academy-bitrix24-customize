<?php


/**
 * @var array $arResult
 */


use Bitrix\Main\Localization\Loc;

defined('B_PROLOG_INCLUDED') || die;

?>

<div class="company__facts">
    <?php if (empty($arResult['facts'])): ?>
        <?= Loc::getMessage('NO_FACTS') ?>
    <?php endif; ?>
    <?php foreach ($arResult['facts'] as $fact): ?>
        <div class="company__fact">
            <h4><?= htmlspecialchars($fact['NAME']) ?></h4>
            <p><?= htmlspecialchars($fact['DETAIL_TEXT']) ?></p>
        </div>
    <?php endforeach; ?>
</div>