<?php

namespace Sholokhov\Sitemap\Source\IBlock;

use Sholokhov\Sitemap\Entry;
use Sholokhov\Sitemap\Exception\SitemapException;
use Sholokhov\Sitemap\Rules\IBlock\IBlockPolicy;
use Sholokhov\Sitemap\Settings\IBlock\IBlockItem;
use Sholokhov\Sitemap\Source\SourceInterface;
use Sholokhov\Sitemap\Normalizer\IBlock\ElementEntryNormalizer;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * Подготовка данных, для генерации карты сайта
 * Производит выборку доступных элементов согласно указанному разделу.
 */
class ElementSource implements SourceInterface
{
    /**
     * Настройки генерации карты сайта для инфоблоков
     *
     * @var IBlockItem
     */
    protected readonly IBlockItem $settings;

    /**
     * Права доступа к элементам сущности
     *
     * @var IBlockPolicy
     */
    protected readonly IBlockPolicy $policy;

    /**
     * Нормализатор ссылки на элемент инфоблока
     *
     * @var ElementEntryNormalizer
     */
    protected ElementEntryNormalizer $normalizer;

    /**
     * ID раздела источника данных
     *
     * @var int
     */
    protected readonly int $sectionId;

    /**
     * Индекс элемента на котором произвели остановку
     *
     * @var int
     */
    protected int $offset = 0;

    /**
     * Количество обрабатываемых элементов за один шаг
     *
     * @var int
     */
    protected int $limit = 10;

    /**
     * Итератор отбираемых значений
     *
     * @var Result|null
     */
    protected Result|null $iterator = null;

    public function __construct($sectionId, IBlockItem $settings)
    {
        if (!Loader::includeModule('iblock')) {
            throw new SitemapException('IBLOCK module is not installed.');
        }

        $this->sectionId = $sectionId;
        $this->settings = $settings;

        $this->policy = new IBlockPolicy($this->settings);

        // TODO Пробросить site id
        $this->normalizer = new ElementEntryNormalizer('s1');
    }

    /**
     * Возвращает элементы карты сайта
     *
     * @inheritDoc
     * @return Entry|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function fetch(): ?Entry
    {
        while (true) {
            if ($this->iterator === null) {
                $this->iterator = $this->query();
            }

            if ($this->iterator->getSelectedRowsCount() === 0) {
                return null;
            }

            $row = $this->iterator->fetch();

            if (!$row) {
                $this->offset += $this->limit;
                $this->iterator = null;
                continue;
            }

            if ($this->isDeny($row)) {
                continue;
            }

            return $this->normalizer->normalize($row);
        }
    }

    /**
     * Указание количество обрабатываемых элементов в один шаг
     *
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Установка стартовой точки
     *
     * @param int $offset
     *
     * @return $this
     */
    public function setOffset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Проверка запрета на добавление в карту сайта
     *
     * @param array $element
     * @return bool
     */
    protected function isDeny(array $element): bool
    {
        $sectionId = (int)($element['IBLOCK_SECTION_ID'] ?? 0);
        $leftMargin = (int)($element['LEFT_MARGIN'] ?? 0);
        $rightMargin = (int)($element['RIGHT_MARGIN'] ?? 0);

        return $this->policy->isDenyElement($sectionId, $leftMargin, $rightMargin);
    }

    /**
     * Выполнить запрос на получение доступных элементов
     *
     * @return Result
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function query(): Result
    {
        return ElementTable::getList([
            'select' => [
                'ID',
                'IBLOCK_ID',
                'TIMESTAMP_X',
                'IBLOCK_SECTION_ID',
                'XML_ID',
                'CODE',
                'RIGHT_MARGIN' => 'IBLOCK_SECTION.RIGHT_MARGIN',
                'LEFT_MARGIN' => 'IBLOCK_SECTION.LEFT_MARGIN',
                'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL',
            ],
            'filter' => [
                '=IBLOCK_ID' => $this->settings->id,
                '=IBLOCK_SECTION_ID' => $this->sectionId,
                '=ACTIVE' => 'Y',
            ],
            'order' => [
                'ID' => 'ASC',
            ],
            'limit' => $this->limit,
            'offset' => $this->offset,
        ]);
    }
}