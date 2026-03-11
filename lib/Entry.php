<?php

namespace Sholokhov\Sitemap;

use DateTimeInterface;

/**
 * Подробная информация добавляемой ссылки в карту сайта
 */
class Entry
{
    /**
     * @param string $url Польный адрес страницы
     * @param DateTimeInterface $lastModificationDate Дата последней модификации страницы
     */
    public function __construct(
        public string $url,
        public DateTimeInterface $lastModificationDate,
    )
    {
    }
}