<?php

namespace Sholokhov\Sitemap\Settings\IBlock;

/**
 * Настройки импорта карты сайта на основе инфоблока
 */
class IBlockSettings
{
    /**
     * @param string $fileName Наименование файла сохранения карты сайта
     * @param IBlockItem[] $items Настройки инфоблоков
     */
    public function __construct(
        public string $fileName,
        public array $items = []
    )
    {
    }
}