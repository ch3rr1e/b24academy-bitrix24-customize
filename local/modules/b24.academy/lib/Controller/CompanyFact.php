<?php

namespace B24\Academy\Controller;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Controller;

class CompanyFact extends Controller
{
    public const FACTS_NUMBER = 10;

    public function getFactAction()
    {
        global $APPLICATION;

        ob_start();
        $APPLICATION->IncludeComponent(
            'b24.academy:company.facts',
            '.default',
            array(
                'FACTS_IBLOCK_ID' => Option::get('b24.academy', 'FACTS_IBLOCK_ID'),
                'FACTS_NUMBER' => self::FACTS_NUMBER,
            )
        );
        $fact = ob_get_clean();
        return ['fact' => $fact];
    }
}