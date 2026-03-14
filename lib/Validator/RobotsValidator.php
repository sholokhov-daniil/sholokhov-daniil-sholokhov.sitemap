<?php

namespace Sholokhov\Sitemap\Validator;

use Sholokhov\Sitemap\Entry;
use Sholokhov\Sitemap\Disassemblers\RobotsDisassembler;

/**
 * Проверка на открытость адреса в рамках файла robots.txt
 */
class RobotsValidator implements ValidatorInterface
{
    /**
     * Адреса исключенные из индексации
     *
     * @var array
     */
    private readonly array $disallow;

    /**
     * @param string $siteId ID сайта, для которого производится проверка robots.txt
     */
    public function __construct(string $siteId)
    {
        $disassembler = new RobotsDisassembler($siteId);
        $this->disallow = $disassembler->getDisallow();
    }

    /**
     * Выполнить проверку
     *
     * @inheritDoc
     * @param Entry $entry
     * @return bool
     */
    public function validate(Entry $entry): bool
    {
        foreach ($this->disallow as $disallow) {
            $pattern = $this->buildPattern($disallow);

            if (preg_match($pattern, $entry->url)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Преобразует путь из robots.txt в регулярное выражение
     *
     * @param string $disallow
     * @return string
     */
    protected function buildPattern(string $disallow): string
    {
        $escaped = str_replace('\*', '.*', preg_quote($disallow, '#'));
        return '#(^.*://.*' . $escaped . ')|(^' . $escaped . ')#';
    }
}