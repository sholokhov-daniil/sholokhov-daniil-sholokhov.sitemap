<?php

namespace Sholokhov\Sitemap\Pipeline;

use CBXPunycode;

use Sholokhov\Sitemap\Configuration;

use Bitrix\Seo\Sitemap\File\Runtime;

class Pipeline implements PipelineInterface
{
    /**
     * Наименование файла в который производится запись ссылок
     *
     * @var string
     * @author Daniil S.
     */
    protected string $filename;

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
     * Валидатор добавляемых ссылок
     * 
     * @var ?object
     */
    protected ?object $validator = null;

    /**
     * Источники данных
     * 
     * @var object[]
     */
    protected array $sources = [];

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
                if ($this->validator && !$this->validator->validate($entry)) {
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
     * Добавление валидатора сохраняемой ссылки
     * 
     * @param object $validator
     * @return $this
     */
    public function setValidator(object $validator): static
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Добавление источника данных
     * 
     * @param object
     * @return static
     */
    public function addSource(object $source): static
    {
        $this->sources[] = $source;
        return $this;
    }

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
            $runtime->split();
            $this->entriesCount = 0;
        }

        $data = [
            'XML_LOC' => $entry->url,
            'XML_LASTMOD' => $entry->lastModificationDate->format('c'),
        ];

        $runtime->addEntry($data);
        // TODO: Добавить запись в хранилище, что ссылка уже добавлена
        $this->entriesCount++;
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