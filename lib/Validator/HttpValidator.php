<?php

namespace Sholokhov\Sitemap\Validator;

use Sholokhov\Sitemap\Entry;
use Sholokhov\Sitemap\Helpers\ContentHelper;

use Bitrix\Main\Web\Uri;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Http\Request;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Производится проверка посредством Http запроса.
 *
 * Производится проверка следующих моментов:
 * - Статуса ответа
 * - Наличие каноничной ссылки
 */
class HttpValidator implements ValidatorInterface
{
    /**
     * @var HttpClient
     */
    protected HttpClient $client;

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
        try {
            $uri = new Uri($entry->url);
            $request = new Request('GET', $uri);
            $response = $this->client->sendRequest($request);

            if (!$this->statusValidate($response)) {
                return false;
            }

            $headerContent = $this->readHeadStreamed($response);

            // TODO: Добавить возможность отключать валидацию canonical
            return $this->canonicalValidate($headerContent, $entry);
        } catch (ClientExceptionInterface) {
            return false;
        }
    }

    /**
     * Проверка статуса ответа.
     *
     * Разрешаются статусы ответа с кодом 2xx
     *
     * @param ResponseInterface $response
     * @return bool
     */
    protected function statusValidate(ResponseInterface $response): bool
    {
        $status = $response->getStatusCode();
        return $status >= 200 && $status < 300;
    }

    /**
     * Проверка каноничной ссылки.
     *
     * @param string $content
     * @param Entry $entry
     * @return bool
     */
    protected function canonicalValidate(string $content, Entry $entry): bool
    {
        $canonical = ContentHelper::getCanonical($content);

        if ($canonical === '') {
            return true;
        }

        return $canonical === $entry->url;
    }

    /**
     * Читаем содержимое страницы до конца header
     *
     * @param ResponseInterface $response
     * @param int $chunkSize
     * @return string
     */
    protected function readHeadStreamed(ResponseInterface $response, int $chunkSize = 4096): string
    {
        $bodyStream = $response->getBody();
        $bodyStream->rewind();

        $headContent = '';
        while (!$bodyStream->eof()) {
            $chunk = $bodyStream->read($chunkSize);
            $headContent .= $chunk;

            if (stripos($headContent, '</head>') !== false) {
                // нашли конец head → обрезаем лишнее
                $headContent = substr($headContent, 0, stripos($headContent, '</head>') + 7);
                break;
            }
        }

        return $headContent;
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