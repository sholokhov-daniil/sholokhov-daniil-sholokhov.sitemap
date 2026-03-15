<?php

namespace Sholokhov\Sitemap\Validator;

use Sholokhov\Sitemap\Entry;

use Bitrix\Main\Web\Uri;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Http\Request;

use Psr\Http\Client\ClientExceptionInterface;
use Sholokhov\Sitemap\Validator\Http\HttpValidatorInterface;

/**
 * Производится проверка посредством HTTP запроса.
 */
class HttpValidator implements ValidatorInterface
{
    /**
     * @var HttpClient
     */
    protected HttpClient $client;

    /**
     * @var HttpValidatorInterface[]
     */
    protected array $features = [];

    public function __construct()
    {
        $this->client = $this->createClient();
    }

    /**
     * Проверить URL.
     *
     * @inheritDoc
     * @param Entry $entry
     * @return bool
     */
    public function validate(Entry $entry): bool
    {
        // TODO: Добавить кеширование валидации

        if (empty($this->features)) {
            return true;
        }

        try {
            $uri = new Uri($entry->url);
            $request = new Request('GET', $uri);
            $response = $this->client->sendRequest($request);

            foreach ($this->features as $feature) {
                if (!$feature->validate($response, $request)) {
                    return false;
                }
            }

            return true;
        } catch (ClientExceptionInterface) {
            return false;
        }
    }

    /**
     * Добавление расширения проверки
     *
     * @param HttpValidatorInterface $feature
     *
     * @return $this
     */
    public function addFeature(HttpValidatorInterface $feature): static
    {
        $this->features[] = $feature;
        return $this;
    }

    /**
     * Создает готовый http client
     *
     * @return HttpClient
     */
    protected function createClient(): HttpClient
    {
        $client = new HttpClient();
        $client->setRedirect(false);
        $client->setStreamTimeout(10);
        $client->setHeader('User-Agent', 'SitemapValidatorBot/1.0');

        return $client;
    }
}