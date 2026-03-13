<?php

namespace Sholokhov\Sitemap\Source\IBlock;

use Sholokhov\Sitemap\Entry;
use Sholokhov\Sitemap\Exception\SitemapException;
use Sholokhov\Sitemap\Rules\IBlock\IBlockPolicy;
use Sholokhov\Sitemap\Settings\IBlock\IBlockItem;
use Sholokhov\Sitemap\Source\SourceInterface;
use Sholokhov\Sitemap\Normalizer\IBlock\SectionEntryNormalizer;

use Bitrix\Main\Loader;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;

/**
 * Источник данных по разделам.
 *
 * Производит поиск активных разделов согласно настройкам карты сайта.
 */
class SectionSource implements SourceInterface
{
    /**
     * Левая граница узла дерева разделов
     *
     * @var int|null
     */
    protected ?int $leftMargin = null;

    /**
     * Правая граница узла дерева разделов
     *
     * @var int|null
     */
    protected ?int $rightMargin = null;

    /**
     * Индекс последнего обработанного раздела
     *
     * @var int
     */
    protected int $offset = 0;

    /**
     * Количество обрабатываемых элемента за один шаг
     *
     * @var int
     */
    protected int $limit = 15;

    /**
     * ID раздела источника данных
     *
     * @var int
     */
    protected readonly int $sectionId;

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
     * Нормализатор ссылки на раздел
     *
     * @var SectionEntryNormalizer
     */
    protected SectionEntryNormalizer $normalizer;

    /**
     * Найденные разделы
     *
     * @var Result|null
     */
    protected ?Result $sectionIterator = null;

    /**
     * Хранилище элементов инфоблока
     *
     * @var ElementSource|null
     */
    protected ?ElementSource $elementSource = null;

    /**
     * @param int $sectionId ID индексируемого раздела. Если указать 0, то индексируются все разделы
     * @param IBlockItem $settings Настройки индексации инфоблока
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SitemapException
     * @throws SystemException
     * @throws LoaderException
     */
    public function __construct(IBlockItem $settings, int $sectionId = 0)
    {
        $this->sectionId = $sectionId;
        $this->settings = $settings;
        $this->policy = new IBlockPolicy($this->settings);

        if (!Loader::includeModule('iblock')) {
            throw new SitemapException('IBLOCK module is not installed.');
        }

        if ($this->sectionId > 0) {
            $this->loadMargins();
        }

        // TODO: Пробросить ID сайта
        $this->normalizer = new SectionEntryNormalizer('s1');
    }

    /**
     * Возвращает ссылку на элемент сущности
     *
     * @return Entry|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SitemapException
     * @throws SystemException
     */
    public function fetch(): ?Entry
    {
        while (true) {
            // 1. Сначала элементы текущего раздела
            if ($this->elementSource !== null) {
                if ($entry = $this->elementSource->fetch()) {
                    return $entry;
                }

                $this->elementSource = null;
            }

            // 2. Инициализация итератора
            if ($this->sectionIterator === null) {
                $this->sectionIterator = $this->getSectionIterator();
            }

            if ($this->sectionIterator->getSelectedRowsCount() === 0) {
                return null;
            }

            $section = $this->sectionIterator->fetch();

            // 3. Страница закончилась → следующая
            if ($section === false) {
                $this->offset += $this->limit;
                $this->sectionIterator = null;
                continue;
            }

            // 4. Раздел запрещён → пропускаем ВСЁ поддерево
            if ($this->isDeny($section)) {
                continue;
            }

            // 5. Раздел разрешён → сначала сам раздел
            $this->elementSource = new ElementSource(
                (int)$section['ID'],
                $this->settings,
            );

            return $this->normalizer->normalize($section);
        }
    }

    /**
     * Указание количество обрабатываемых раздела в один шаг
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
     * Проверка запрета добавления раздела в карту сайта
     *
     * @param array $section
     * @return bool
     */
    protected function isDeny(array $section): bool
    {
        return $this->policy->isDenySection($section['LEFT_MARGIN'], $section['RIGHT_MARGIN']);
    }

    /**
     * Формирует итератор найденных разделов
     *
     * @return Result|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function getSectionIterator(): ?Result
    {
        $filter = [
            '=IBLOCK_ID' => $this->settings->id,
            '=ACTIVE' => 'Y',
        ];

        if ($this->sectionId > 0) {
            $filter['>LEFT_MARGIN'] = $this->leftMargin;
            $filter['<RIGHT_MARGIN'] = $this->rightMargin;
        }

        return SectionTable::getList([
            'select' => [
                'ID',
                'NAME',
                'CODE',
                'XML_ID',
                'TIMESTAMP_X',
                'LEFT_MARGIN',
                'RIGHT_MARGIN',
                'IBLOCK_SECTION_ID',
                'SECTION_PAGE_URL' => 'IBLOCK.SECTION_PAGE_URL',
            ],
            'filter' => $filter,
            'order' => [
                'LEFT_MARGIN' => 'ASC',
            ],
            'limit' => $this->limit,
            'offset' => $this->offset,
        ]);
    }

    /**
     * Определение границ разделов
     *
     * @return void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SitemapException
     * @throws SystemException
     */
    protected function loadMargins(): void
    {
        $section = SectionTable::getByPrimary($this->sectionId, [
            'select' => ['LEFT_MARGIN', 'RIGHT_MARGIN']
        ])->fetch();

        if (!$section) {
            throw new SitemapException("Section with ID {$this->sectionId} not found");
        }

        $this->leftMargin = (int)$section['LEFT_MARGIN'];
        $this->rightMargin = (int)$section['RIGHT_MARGIN'];
    }
}