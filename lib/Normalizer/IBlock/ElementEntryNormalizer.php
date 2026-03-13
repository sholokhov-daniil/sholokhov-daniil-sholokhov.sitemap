<?php

namespace Sholokhov\Sitemap\Normalizer\IBlock;

use Bitrix\Main\Loader;
use CIBlock;
use DateTime;
use Sholokhov\Sitemap\Entry;
use Sholokhov\Sitemap\Exception\SitemapException;

/**
 * Преобразует массив данных элемента в формат записи sitemap
 */
class ElementEntryNormalizer
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
    public function normalize(array $element): Entry
    {
        $element['LANG'] = $this->siteId;

        $url = CIBlock::ReplaceDetailUrl($element['DETAIL_PAGE_URL'], $element, false, "E");
        $date = DateTime::createFromTimestamp($element['TIMESTAMP_X']->getTimestamp());

        return new Entry($url, $date);
    }
}
