<?php

namespace Sholokhov\Sitemap;

use Sholokhov\Sitemap\Exception\SitemapException;

use Bitrix\Main\SiteTable;
use Bitrix\Main\Type\Contract\Arrayable;

/**
 * Конфигурация генератора карты сайта
 */
class Configuration implements Arrayable
{
    public function __construct(
        public readonly string $siteId,
        public string $protocol = 'https',
        public string $domain = ''
    )
    {
    }

    /**
     * Создание конфигурации на основе настроек сайта
     *
     * @param string $siteId
     * @return self
     */
    public static function createFromSiteId(string $siteId): self
    {
        $site = SiteTable::getByPrimary($siteId)->fetchObject();

        if (!$site) {
            throw new SitemapException("Site $siteId not found");
        }

        return new self(
            siteId: $siteId,
            domain: $site->get('SERVER_NAME')
        );
    }

    /**
     * Преобразовывает объект в массив
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'SITE_ID' => $this->siteId,
            'PROTOCOL' => $this->protocol,
            'DOMAIN' => $this->domain,
        ];
    }
}