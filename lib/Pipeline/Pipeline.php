<?php

namespace Sholokhov\Sitemap\Pipeline;

use Bitrix\Main\Diag\Debug;
use CBXPunycode;

use Sholokhov\Sitemap\Configuration;

use Bitrix\Seo\Sitemap\File\Runtime;

class Pipeline implements PipelineInterface
{
    use UseSourceTrait, UseValidatorTrait, UseSplittingTrait;

    /**
     * Наименование файла в который производится запись ссылок
     *
     * @var string
     * @author Daniil S.
     */
    protected string $filename;

    /**
     * @param string $filename Наименование файла в который производится сохранение ссылок
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * Выполнить генерацию
     *
     * @param Configuration $config
     *
     * @return Runtime
     */
    public function run(Configuration $config): Runtime
    {
        $runtime = new Runtime($config->siteId, $this->getFileName(), $config->toArray());

        foreach($this->sources as $source) {
            while($entry = $source->fetch()) {
                if (!$this->isEntryValidation($entry)) {
                    continue;
                }

                $this->modify($entry, $config);
                $this->addEntry($entry, $runtime);
            }
        }

        return $runtime;
    }

    /**
     * Возвращает название файла в который производится запись
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->filename;
    }

    /**
     * Модифицировать адрес
     *
     * @param object $entry
     * @param Configuration $config
     *
     * @return void
     */
    protected function modify(object $entry, Configuration $config): void
    {
        $errors = [];
        $host = $config->protocol
            . '://'
            . CBXPunycode::toASCII($config->domain, $errors);

        if (!str_starts_with($entry->url, $host)) {
            $entry->url = $host . $entry->url;
        }
    }

    /**
     * Добавление ссылки в карту сайта
     *
     * @param object $entry
     * @param Runtime $runtime
     * @return void
     */
    protected function addEntry(object $entry, Runtime $runtime): void
    {
        // TODO: Добавить проверку на дублирование

        if ($this->isSplitNeeded()) {
            $this->split($runtime);
        }

        $data = [
            'XML_LOC' => $entry->url,
            'XML_LASTMOD' => $entry->lastModificationDate->format('c'),
        ];

        $runtime->addEntry($data);
        // TODO: Добавить запись в хранилище, что ссылка уже добавлена
        $this->entriesCount++;
    }
}