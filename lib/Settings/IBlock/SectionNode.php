<?php

namespace Sholokhov\Sitemap\Settings\IBlock;

/**
 * Настройки раздела инфоблока
 */
class SectionNode
{
    /**
     * @param int $id ID генерируемого пункта. В качестве ID выступает ID элемента, раздела или иного вида
     * @param int[] $excludedElements Игнорируемые элементы
     * @param bool $loadSections В генерации участвуют вложенные разделы
     * @param bool $loadElements В генерации участвуют вложенные элементы
     */
    public function __construct(
        public readonly int $id,
        public array $excludedElements = [],
        public bool $loadSections = true,
        public bool $loadElements = true
    )
    {
    }
}