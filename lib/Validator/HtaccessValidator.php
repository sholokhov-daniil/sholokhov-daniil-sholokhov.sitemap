<?php

namespace Sholokhov\Sitemap\Validator;

use Sholokhov\Sitemap\Entry;

use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Web\Uri;

/**
 * Валидатор проверяет, что URL не попадает под редиректы в .htaccess
 *
 * Поддерживаются команды:
 * - Redirect
 * - RedirectMatch
 * - RewriteRule + RewriteCond
 */
class HtaccessValidator implements ValidatorInterface
{
    /**
     * Кэш правил по сайту
     *
     * @var array<int, array{TYPE:string, FROM:string, TO:string, FLAGS?:array, CONDITIONS?:array}>
     */
    protected array $rules = [];

    /**
     * Файл .htaccess
     *
     * @var File
     */
    protected readonly File $file;

    /**
     * @param string $siteId ID сайта на основе которого производится проверка
     */
    public function __construct(string $siteId)
    {
        $this->file = $this->searchFile($siteId);
    }

    /**
     * Проверка возможности добавить ссылку в sitemap
     *
     * @inheritDoc
     * @param Entry $entry
     * @return bool
     * @throws FileNotFoundException
     */
    public function validate(Entry $entry): bool
    {
        if (empty($this->rules)) {
            $this->load();
        }

        $path = (new Uri($entry->url))->getPath();

        foreach ($this->rules as $rule) {
            if ($this->isRedirected($rule, $path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Определяет, соответствует ли путь редиректу
     *
     * @param array $rule
     * @param string $path
     * @return bool
     */
    protected function isRedirected(array $rule, string $path): bool
    {
        return match ($rule['TYPE']) {
            'Redirect' => $this->matchRedirect($rule['FROM'], $path),
            'RedirectMatch' => $this->matchRedirectMatch($rule['FROM'], $path),
            'RewriteRule' => $this->matchRewriteRule($rule, $path),
            default => false,
        };
    }

    /**
     * Проверка Redirect с учётом точного совпадения и boundary
     *
     * @param string $from
     * @param string $path
     * @return bool
     */
    protected function matchRedirect(string $from, string $path): bool
    {
        return $path === $from || str_starts_with($path, rtrim($from, '/') . '/');
    }

    /**
     * Проверка RedirectMatch через регулярное выражение
     *
     * @param string $pattern
     * @param string $path
     * @return bool
     */
    protected function matchRedirectMatch(string $pattern, string $path): bool
    {
        return preg_match('#' . $pattern . '#', $path) === 1;
    }

    /**
     * Проверка RewriteRule через регулярное выражение
     *
     * @param array $rule
     * @param string $path
     * @return bool
     */
    protected function matchRewriteRule(array $rule, string $path): bool
    {
        if (empty($rule['FLAGS']) || !array_filter($rule['FLAGS'], fn($f) => str_starts_with($f, 'R'))) {
            return false;
        }

        foreach ($rule['CONDITIONS'] ?? [] as $condPattern) {
            if (!$this->matchRedirectMatch($condPattern, $path)) {
                return false;
            }
        }

        return $this->matchRedirectMatch('^' . $rule['FROM'], $path);
    }

    /**
     * Поиск htaccess файла
     *
     * @param string $siteId
     * @return File
     */
    protected function searchFile(string $siteId): File
    {
        $path = SiteTable::getDocumentRoot($siteId) . DIRECTORY_SEPARATOR . ".htaccess";
        return new File($path, $siteId);
    }

    /**
     * Загрузка правил из .htaccess
     *
     * @throws FileNotFoundException
     */
    protected function load(): void
    {
        $this->rules = [];

        if (!$this->file->isExists()) {
            return;
        }

        $lines = preg_split("/\r?\n/", $this->file->getContents()) ?: [];

        $rewriteConds = [];

        foreach ($lines as $line) {
            $line = $this->cleanLine($line);
            if ($line === '') {
                continue;
            }

            if ($this->parseRedirect($line) || $this->parseRewrite($line, $rewriteConds)) {
                continue;
            }

            // Если это RewriteCond, добавляем в массив условий
            if (preg_match('#^RewriteCond\s+\S+\s+(.*)$#i', $line, $matches)) {
                $rewriteConds[] = trim($matches[1]);
            }
        }
    }

    /**
     * Чистим строку: trim и удаляем комментарии
     *
     * @param string $line
     * @return string
     */
    protected function cleanLine(string $line): string
    {
        $line = trim($line);
        if (($pos = mb_strpos($line, '#')) !== false) {
            $line = mb_substr($line, 0, $pos);
        }
        return trim($line);
    }

    /**
     * Парсим Redirect и RedirectMatch
     *
     * @param string $line
     * @return bool
     */
    protected function parseRedirect(string $line): bool
    {
        if (preg_match('#^(Redirect|RedirectMatch)\s+(\d{3})\s+(\S+)\s+(\S+)$#i', $line, $matches)) {
            [, $type, $status, $from, $to] = $matches;

            if ($type === 'Redirect') {
                $from = '/' . ltrim($from, '/');
                $to = '/' . ltrim($to, '/');
            }

            $this->rules[] = [
                'TYPE' => $type,
                'FROM' => $from,
                'TO' => $to,
                'STATUS' => (int)$status,
            ];

            return true;
        }

        return false;
    }

    /**
     * Парсим RewriteRule
     *
     * @param string $line
     * @param array $rewriteConds
     * @return bool
     */
    protected function parseRewrite(string $line, array &$rewriteConds): bool
    {
        if (preg_match('#^RewriteRule\s+(\S+)\s+(\S+)(?:\s+\[(.*)\])?$#i', $line, $matches)) {
            [, $pattern, $to, $flagsStr] = array_pad($matches, 4, '');
            $flags = $flagsStr !== '' ? array_map('trim', explode(',', $flagsStr)) : [];

            $this->rules[] = [
                'TYPE' => 'RewriteRule',
                'FROM' => $pattern,
                'TO' => $to,
                'FLAGS' => $flags,
                'CONDITIONS' => $rewriteConds,
            ];

            $rewriteConds = [];
            return true;
        }

        return false;
    }
}
