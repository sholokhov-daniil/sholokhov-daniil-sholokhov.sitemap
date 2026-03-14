<?php

namespace Sholokhov\Sitemap\Validator\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sholokhov\Sitemap\Helpers\ContentHelper;

/**
 * Проверка корректности canonical
 */
class CanonicalValidator implements HttpValidatorInterface
{
    /**
     * Производит проверку
     *
     * @param ResponseInterface $response
     * @param RequestInterface $request
     *
     * @return bool
     */
    public function validate(ResponseInterface $response, RequestInterface $request): bool
    {
        $content = $this->readHeadStreamed($response);
        return $this->canonicalValidate($content, $request);
    }

    /**
     * Проверяет корректность canonical
     *
     * @param string $content
     * @param RequestInterface $request
     *
     * @return bool
     */
    protected function canonicalValidate(string $content, RequestInterface $request): bool
    {
        $canonical = ContentHelper::getCanonical($content);

        if ($canonical === '') {
            return true;
        }

        return $canonical === (string)$request->getUri();
    }

    /**
     * Читаем содержимое страницы до конца header
     *
     * @param ResponseInterface $response
     * @param int $chunkSize
     *
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
}