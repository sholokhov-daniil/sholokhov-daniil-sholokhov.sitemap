<?php

namespace Sholokhov\Sitemap\Rules\IBlock;

use Sholokhov\Sitemap\Settings\IBlock\IBlockItem;

/**
 * Политика доступности разделов
 */
class SectionPolicy
{
    /**
     * Политика запрета на основе margin
     * @var SectionMarginPolicy
     */
    private SectionMarginPolicy $policy;

    /**
     * @param IBlockItem $settings Настройки генерации карты сайта для инфоблоков
     */
    public function __construct(IBlockItem $settings)
    {
        $this->policy = $this->createMarginPolicy($settings);
    }

    /**
     * Проверяем запрет на использование раздела
     *
     * @param int $leftMargin
     * @param int $rightMargin
     * @return bool
     */
    public function isDeny(int $leftMargin, int $rightMargin): bool
    {
        return $this->policy->isDeny($leftMargin, $rightMargin);
    }

    private function createMarginPolicy(IBlockItem $settings): SectionMarginPolicy
    {
        $ids = array_unique($settings->executedSections);
        return new SectionMarginPolicy($ids);
    }
}