<?php

namespace Sholokhov\Sitemap\Pipeline;

use Sholokhov\Sitemap\Source\SourceInterface;

trait UseSourceTrait
{
    /**
     * Источники данных
     *
     * @var SourceInterface[]
     */
    protected array $sources = [];

    /**
     * Добавление источника данных
     *
     * @param SourceInterface
     * @return static
     */
    public function addSource(SourceInterface $source): static
    {
        $this->sources[] = $source;
        return $this;
    }
}