<?php

namespace Sholokhov\Sitemap\Source\IO;

use CSeoUtils;
use DateTime;
use Generator;

use Sholokhov\Sitemap\Entry;
use Sholokhov\Sitemap\Source\SourceInterface;

use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\IO\InvalidPathException;
use Bitrix\Main\IO\Path;
use Bitrix\Main\SiteTable;

class IOSource implements SourceInterface
{
    /**
     * Элементы файловой системы принимающие участие в формировании доступных ссылок
     *
     * @var IOEntry[]
     */
    protected readonly array $items;

    /**
     * Корневая директория сайта анализа файловой структуры
     *
     * @var string
     */
    protected readonly string $documentRoot;

    /**
     * ID файла, которого анализируется файловая структура
     *
     * @var string
     */
    protected readonly string $siteId;

    /**
     * Поток элементов файловой структуры
     *
     * @var Generator|null
     */
    protected ?Generator $stream = null;

    /**
     * @param string $siteId ID сайта принимающий участие в анализе
     * @param IOEntry[] $items Элементы файловой системы принимающие участие в анализе доступных ссылок
     */
    public function __construct(string $siteId, array $items = [])
    {
        $this->siteId = $siteId;
        $this->documentRoot = (string)SiteTable::getDocumentRoot($this->siteId);
        $this->items = $items;
    }

    /**
     * Возвращает доступную ссылку
     *
     * @inheritDoc
     * @return Entry|null
     * @throws FileNotFoundException
     * @throws InvalidPathException
     */
    public function fetch(): ?Entry
    {
        if (empty($this->items)) {
            return null;
        }

        if ($this->stream === null) {
            $this->stream = $this->iterateSources();
        }

        if (!$this->stream->valid()) {
            return null;
        }

        $current = $this->stream->current();
        $this->stream->next();

        return $current;
    }

    /**
     * Итерация по пользовательским источникам файловой системы
     *
     * @return Generator
     * @throws FileNotFoundException
     * @throws InvalidPathException
     */
    protected function iterateSources(): Generator
    {
        foreach ($this->items as $entity) {
            if (!$entity->active) {
                continue;
            }

            switch ($entity->type) {
                case EntryType::File:
                    yield $this->mapFileToEntry(
                        new File($entity->path, $this->siteId)
                    );
                    break;
                case EntryType::Directory:
                    yield from $this->walkDirectory($entity->path);
                    break;
            }
        }
    }

    /**
     * Ленивый рекурсивный обход директории
     *
     * @param string $directoryPath
     *
     * @return Generator
     * @throws FileNotFoundException
     * @throws InvalidPathException
     */
    protected function walkDirectory(string $directoryPath): Generator
    {
        $list = CSeoUtils::getDirStructure(true, $this->siteId, $directoryPath);

        foreach ($list as $item) {
            if ($item['TYPE'] === EntryType::File->value) {
                yield $this->mapFileToEntry(
                    new File($item['DATA']['PATH'], $this->siteId)
                );
                continue;
            }

            $nextDir = DIRECTORY_SEPARATOR . ltrim(
                    $item['DATA']['ABS_PATH'],
                    DIRECTORY_SEPARATOR
                );

            yield from $this->walkDirectory($nextDir);
        }
    }

    /**
     * Преобразование файла в элемент sitemap
     *
     * @param File $file
     * @return Entry
     * @throws FileNotFoundException
     * @throws InvalidPathException
     */
    protected function mapFileToEntry(File $file): Entry
    {
        $date = DateTime::createFromTimestamp($file->getModificationTime());
        $url = $this->getFileUrl($file, $this->documentRoot);

        return new Entry($url, $date);
    }

    /**
     * Формирование полного URL пути до файла
     *
     * @param File $file
     * @param string $documentRoot
     *
     * @return string
     * @throws InvalidPathException
     */
    protected function getFileUrl(File $file, string $documentRoot): string
    {
        $documentRoot = Path::normalize($documentRoot);
        $path = '/';

        if (mb_substr($file->getPath(), 0, mb_strlen($documentRoot)) === $documentRoot) {
            $path = '/' . mb_substr($file->getPath(), mb_strlen($documentRoot));
        }

        $path = Path::convertLogicalToUri($path);

        $path = in_array($file->getName(), GetDirIndexArray())
            ? str_replace('/' . $file->getName(), '/', $path)
            : $path;

        return '/' . ltrim($path, '/');
    }
}