<?php

namespace Sholokhov\Sitemap\Rules\IBlock;

use Sholokhov\Sitemap\Exception\SitemapException;
use Sholokhov\Sitemap\Settings\IBlock\IBlockItem;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * Политика доступности элементов
 */
class ElementPolicy
{
    /**
     * Политика доступности по разделам
     *
     * @var SectionPolicy
     */
    protected SectionPolicy $sectionPolicy;

    /**
     * Список запрещенных элементов по разделам
     *
     * @var SectionMarginPolicy
     */
    protected SectionMarginPolicy $elementPolicy;

    /**
     * @param IBlockItem $settings Настройки генерации карты сайта для инфоблоков
     * @param SectionPolicy $sectionPolicy Политика доступности разделов
     */
    public function __construct(IBlockItem $settings, SectionPolicy $sectionPolicy)
    {
        $this->sectionPolicy = $sectionPolicy;
        $this->elementPolicy = $this->createPolicy($settings);
    }

    /**
     * Проверка запрета на использование элемента
     *
     * @param int $sectionId
     * @param int $leftMargin
     * @param int $rightMargin
     * @return bool
     */
    public function isDeny(int $sectionId, int $leftMargin, int $rightMargin): bool
    {
        return $this->sectionPolicy->isDeny($leftMargin, $sectionId)
            || $this->elementPolicy->isDeny($rightMargin, $sectionId);
    }

    /**
     * Создание политики запрещенных элементов по разделам
     *
     * @param IBlockItem $settings
     * @return SectionMarginPolicy
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws SitemapException
     */
    private function createPolicy(IBlockItem $settings): SectionMarginPolicy
    {
        $ids = array_unique($settings->executedSectionElements);
        return new SectionMarginPolicy($ids);
    }
}