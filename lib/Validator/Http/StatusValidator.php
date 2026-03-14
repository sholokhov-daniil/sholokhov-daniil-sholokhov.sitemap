<?php

namespace Sholokhov\Sitemap\Validator\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Проверка статуса ответа страницы
 */
class StatusValidator implements HttpValidatorInterface
{
    protected const STATUS_OK = 200;
    protected const STATUS_MULTIPLE_CHOICES = 300;

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
        $status = $response->getStatusCode();
        return $status >= self::STATUS_OK && $status < static::STATUS_MULTIPLE_CHOICES;
    }
}