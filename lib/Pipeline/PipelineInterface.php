<?php

namespace Sholokhov\Sitemap\Pipeline;

use Sholokhov\Sitemap\Configuration;
use Bitrix\Seo\Sitemap\File\Runtime;

/**
 * Процесс генерации файла карты сайта
 */
interface PipelineInterface
{
    /**
     * Выполнение задачи
     * 
     * @return Runtime
     */
    public function run(Configuration $config): Runtime;
}