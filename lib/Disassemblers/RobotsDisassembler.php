<?php

namespace Sholokhov\Sitemap\Disassemblers;

use Bitrix\Seo\RobotsFile;

/**
 * Производит манипуляции с robots.txt
 */
class RobotsDisassembler
{
    /**
     * Механизм работы с robots.txt
     *
     * @var RobotsFile
     */
    protected RobotsFile $robots;

    /**
     * @param string $siteId ID сайта на основе которого производится подгрузка robots
     */
    public function __construct(string $siteId)
    {
        $this->robots = new RobotsFile($siteId);
    }

    /**
     * Получение страниц закрытых от индексации.
     *
     * @return array
     */
    public function getDisallow(): array
    {
        return $this->loadRules('disallow');
    }

    /**
     * Загрузка доступных ролей.
     *
     * @param string $rule
     * @param string $section
     * @return array
     */
    public function loadRules(string $rule, string $section = '*'): array
    {
        $result = [];
        $ruleGroup = $this->robots->getRules($rule, $section);

        foreach ($ruleGroup as $item) {
            if (!in_array($item[1], $result)) {
                $result[] = $item[1];
            }
        }

        return $result;
    }

    /**
     * Получение механизма работы с robots.txt
     *
     * @return RobotsFile
     */
    public function getEntity(): RobotsFile
    {
        return $this->robots;
    }
}