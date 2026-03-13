<?php

namespace Sholokhov\Sitemap\Rules\IBlock;

use Sholokhov\Sitemap\Exception\SitemapException;

use Bitrix\Main\Loader;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class SectionMarginPolicy
{
    /**
     * Правила доступности
     *
     * @var array
     */
    private array $rules;

    /**
     * @param array $sectionIds Список ID запрещенных разделов по которым строится margin
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SitemapException
     * @throws SystemException
     */
    public function __construct(array $sectionIds)
    {
        $this->rules = $this->getDenyMargin($sectionIds);
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
        return array_any(
            $this->rules,
            fn($rule) => $leftMargin >= $rule['l'] && $rightMargin <= $rule['r']
        );

    }

    /**
     * Формируем список запрещенных вложенностей разделов
     *
     * @param array $ids
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SitemapException
     * @throws SystemException
     */
    private function getDenyMargin(array $ids): array
    {
        if (!Loader::includeModule('iblock')) {
            throw new SitemapException('Module "iblock" not installed');
        }

        $iterator = SectionTable::getList([
            'filter' => ['=ID' => $ids],
            'select' => ['LEFT_MARGIN', 'RIGHT_MARGIN'],
        ]);

        $rules = [];

        while ($row = $iterator->fetch()) {
            $rules[] = [
                'l' => (int)$row['LEFT_MARGIN'],
                'r' => (int)$row['RIGHT_MARGIN'],
            ];
        }

        return $this->mergeRanges($rules);
    }

    /**
     * Исключаем лишние правила
     *
     * @param array $ranges
     * @return array
     */
    private function mergeRanges(array $ranges): array
    {
        usort($ranges, fn($a, $b) => $a['l'] <=> $b['l']);

        $result = [];

        foreach ($ranges as $range) {
            if ($result === []) {
                $result[] = $range;
                continue;
            }

            $last = &$result[array_key_last($result)];

            if ($range['r'] <= $last['r']) {
                continue;
            }

            $result[] = $range;
        }

        return $result;
    }
}