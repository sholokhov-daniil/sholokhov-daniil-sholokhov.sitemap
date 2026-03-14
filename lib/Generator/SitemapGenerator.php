<?php

namespace Sholokhov\Sitemap\Generator;

use Bitrix\Main\Diag\Debug;
use Sholokhov\Sitemap\Configuration;
use Sholokhov\Sitemap\Pipeline\PipelineInterface;

use Bitrix\Seo\Sitemap\File\Index;

class SitemapGenerator
{
    /**
     * Процессы генерации файлов карты сайта
     * 
     * @var PipelineInterface[]
     */
    private array $pipelines = [];

    /**
     * Наименование индексной страницы карты сайта
     *
     * @var string
     * @return void
     */
    private string $indexFileName = 'sitemap.xml';

    /**
     * Конфигурация процесса создания файла карты сайта
     * 
     * @var Configuration $config
     */
    protected Configuration $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Запустить генерацию карты сайта
     * 
     * @return void
     */
    public function run(): void
    {
        $index = new Index($this->indexFileName, $this->config->toArray());

        $files = array_map(
            fn(PipelineInterface $pipeline) => $pipeline->run($this->config),
            $this->pipelines 
        );

        foreach ($files as $runtime) {
            if ($runtime->isCurrentPartNotEmpty()) {
                $runtime->finish();
            } elseif ($runtime->isExists()) {
                $runtime->delete();
            }
        }

        $index->createIndex($files);
    }

    /**
     * Добавление процесса генерации
     *
     * @param PipelineInterface $pipeline
     *
     * @return $this
     * @author Daniil S.
     */
    public function addPipeline(PipelineInterface $pipeline): static
    {
        $this->pipelines[] = $pipeline;
        return $this;
    }

    /**
     * Устанавливает название идексного файла карты сайта
     * 
     * @param string $name
     * 
     * @return $this
     */
    public function setIndexFileName(string $name): static
    {
        $this->indexFileName = $name;
        return $this;
    }
}