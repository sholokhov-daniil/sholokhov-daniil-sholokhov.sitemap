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
     * @var string[]
     */
    protected array $includes;

    /**
     * Элементы файловой системы исключенные из формирования доступных ссылок
     *
     * @var string[]
     */
    protected array $excludes;

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
     * @param array $includes
     * @param array $excludes
     */
    public function __construct(string $siteId, array $includes = [], array $excludes = [])
    {
        $this->siteId = $siteId;
        $this->documentRoot = (string)SiteTable::getDocumentRoot($this->siteId) . DIRECTORY_SEPARATOR;

        $this->includes = $includes;
        $this->excludes = array_map(
            $this->normalizePath(...),
            $excludes
        );
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
        if (empty($this->includes)) {
            return null;
        }

        if (is_null($this->stream)) {
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
        foreach ($this->includes as $path) {
            if ($this->isExcluded($path)) {
                continue;
            }

            if (is_dir($path)) {
                yield from $this->walkDirectory($path);
                continue;
            }

            yield $this->mapFileToEntry(
                new File($path, $this->siteId)
            );
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
            $path = $item['DATA']['PATH'];

            if ($this->isExcluded($path)) {
                continue;
            }

            if ($item['TYPE'] === 'F') {
                yield $this->mapFileToEntry(
                    new File($path, $this->siteId)
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
     * Проверяет исключения
     *
     * @param string $path
     *
     * @return bool
     * @throws InvalidPathException
     * @author Daniil S.
     */
    protected function isExcluded(string $path): bool
    {
        $path = $this->normalizePath($path);

        foreach ($this->excludes as $exclude) {
            if ($path === $exclude) {
                return true;
            }

            if (str_starts_with($path, $exclude)) {
                return true;
            }
        }

        return false;
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

    /**
     * Нормализация пути до элемента каталога
     *
     * @param string $path
     *
     * @return string
     * @author Daniil S.
     */
    protected function normalizePath(string $path): string
    {
        return str_starts_with($path, $this->documentRoot)
            ? $path
            : $this->documentRoot . ltrim($path, DIRECTORY_SEPARATOR);
    }
}