<?php

namespace Sholokhov\Sitemap\Normalizer\IBlock;

use CIBlock;
use DateTime;
use Sholokhov\Sitemap\Entry;
use Bitrix\Seo\Sitemap\Source\Iblock;

/**
 * Преобразует массив данных раздела в формат записи sitemap
 */
class SectionEntryNormalizer
{
    public function __construct(protected string $siteId)
    {
    }

    /**
     * Выполнить нормализацию
     *
     * @param array $section
     *
     * @return Entry
     */
    public function normalize(array $section): Entry
    {
        $section['SECTION_PAGE_URL'] = Iblock::prepareUrlToReplace($section['SECTION_PAGE_URL'], $this->siteId);
        $section['SECTION_PAGE_URL'] = CIBlock::ReplaceDetailUrl($section['SECTION_PAGE_URL'], $section, false, 'S');

        /** @var \Bitrix\Main\Type\DateTime $date */
        $bxDate = $section['TIMESTAMP_X'];
        $date = DateTime::createFromTimestamp($bxDate->getTimestamp());
        $url = $section['SECTION_PAGE_URL'];

        return new Entry($url, $date);
    }
}
