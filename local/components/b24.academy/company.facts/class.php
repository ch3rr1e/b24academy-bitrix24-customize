<?php
defined('B_PROLOG_INCLUDED') || die;

class CompanyFactsComponent extends CBitrixComponent
{
    private const FACTS_NUMBER = 10;

    public function executeComponent()
    {
        $factsIblockId = $this->arParams['FACTS_IBLOCK_ID'];
        $factsNumber = $this->arParams['FACTS_NUMBER'] ?? CompanyFactsComponent::FACTS_NUMBER;

        $result = CIBlockElement::GetList(
            arFilter: ['IBLOCK_ID' => $factsIblockId],
            arNavStartParams: ['nPageSize' => $factsNumber],
        );

        $facts = [];
        while ($fact = $result->Fetch()) {
            $facts[] = $fact;
        }
        $this->arResult['facts'] = $facts;
        $this->includeComponentTemplate();
    }
}