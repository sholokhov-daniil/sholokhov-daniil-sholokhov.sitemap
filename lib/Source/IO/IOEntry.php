<?php

namespace Sholokhov\Sitemap\Source\IO;

class IOEntry
{
    /**
     * @param string $path Путь до файла\директории
     * @param EntryType $type Тип элемента
     * @param bool $active Добавить в карту сайта
     */
    public function __construct(
        public string $path,
        public EntryType $type,
        public bool $active = true,
    )
    {
    }
}