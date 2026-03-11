<?php

namespace Sholokhov\Sitemap\Source\IO;

/**
 * Справочник доступных типов элементов каталога
 */
enum EntryType: string
{
    case File = 'F';
    case Directory = 'D';
}
