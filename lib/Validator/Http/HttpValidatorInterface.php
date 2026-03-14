<?php

namespace Sholokhov\Sitemap\Validator\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Интерфейс проверки HTTP ответа страницы
 */
interface HttpValidatorInterface
{
    /**
     * Проверка корректности http ответа
     *
     * @param ResponseInterface $response Ответ страницы
     * @param RequestInterface $request Запрос к  странице
     *
     * @return bool
     */
    public function validate(ResponseInterface $response, RequestInterface $request): bool;
}