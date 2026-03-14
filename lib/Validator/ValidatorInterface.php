<?php

namespace Sholokhov\Sitemap\Validator;

use Sholokhov\Sitemap\Entry;

/**
 * Производит проверку возможности добавления адреса в карту сайта
 */
interface ValidatorInterface
{
    /**
     * Проверка валидности адреса
     *
     * @param Entry $entry
     * @return bool
     */
    public function validate(Entry $entry): bool;
}