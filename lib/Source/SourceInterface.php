<?php

namespace Sholokhov\Sitemap\Source;

use Sholokhov\Sitemap\Entry;

/**
 * Источник списка URL адресов необходимых добавить в карту сайта
 */
interface SourceInterface
{
    /**
     * Возвращает информацию по добавляемой ссылке
     *
     * @return Entry|null
     */
    public function fetch(): ?Entry;
}