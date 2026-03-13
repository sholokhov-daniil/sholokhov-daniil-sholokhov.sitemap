<?php

namespace Sholokhov\Sitemap\Rules\IBlock;

use Sholokhov\Sitemap\Settings\IBlock\IBlockItem;
use Sholokhov\Sitemap\Settings\IBlock\IBlockSettings;

/**
 * Управляет правами доступа инфоблока на участие в генерации карты сайта
 *
 * Определяет права доступа на участие разделов и элементов
 */
class IBlockPolicy
{
    /**
     * @var SectionPolicy Политика доступности разделов
     */
    private SectionPolicy $sectionPolicy;

    /**
     * @var ElementPolicy Политика доступности элементов
     */
    private ElementPolicy $elementPolicy;

    public function __construct(IBlockItem $settings)
    {
        $this->sectionPolicy = new SectionPolicy($settings);
        $this->elementPolicy = new ElementPolicy($settings, $this->sectionPolicy);
    }

    /**
     * Проверка запрета раздела на участие в генерации раздела
     *
     * @param int $leftMargin
     * @param int $rightMargin
     * @return bool
     */
    public function isDenySection(int $leftMargin, int $rightMargin): bool
    {
        return $this->sectionPolicy->isDeny($leftMargin, $rightMargin);
    }

    /**
     * Проверка запрета элемента на участие в генерации карты сайта
     *
     * @param int $sectionId
     * @param int $leftMargin
     * @param int $rightMargin
     * @return bool
     */
    public function isDenyElement(int $sectionId, int $leftMargin, int $rightMargin): bool
    {
        return $this->elementPolicy->isDeny($sectionId, $leftMargin, $rightMargin);
    }
}