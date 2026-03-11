<?php

namespace Sholokhov\Sitemap\Pipeline;

use Bitrix\Seo\Sitemap\File\Runtime;

trait UseSplittingTrait
{
    /**
     * Максимальное количество записей в одном файле карты сайта
     *
     * @var int
     */
    protected int $maxEntries = 0;

    /**
     * Количество добавленных ссылок в файл карты сайта
     *
     * @var int
     */
    protected int $entriesCount = 0;

    /**
     * Установка максимального количества записей в одном файле
     *
     * @param int $count
     * @return static
     */
    public function setMaxEntries(int $count): static
    {
        $this->maxEntries = $count;
        return $this;
    }

    /**
     * Создает следующую часть файла карты сайта. Возвращает имя файла новой части
     *
     * @param Runtime $runtime
     *
     * @return void
     */
    protected function split(Runtime $runtime): void
    {
        $runtime->split();
        $this->entriesCount = 0;
    }

    /**
     * Определение необходимости разделения файла карты сайта.
     *
     * @return bool
     */
    protected function isSplitNeeded(): bool
    {
        return $this->maxEntries > 0 && $this->entriesCount >= $this->maxEntries;
    }
}