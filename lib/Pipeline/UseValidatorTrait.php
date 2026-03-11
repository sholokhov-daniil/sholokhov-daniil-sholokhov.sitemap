<?php

namespace Sholokhov\Sitemap\Pipeline;

use Sholokhov\Sitemap\Entry;

trait UseValidatorTrait
{
    /**
     * Валидатор добавляемых ссылок
     *
     * @var ?object
     */
    protected ?object $validator = null;

    /**
     * Добавление валидатора сохраняемой ссылки
     *
     * @param object $validator
     * @return $this
     */
    public function setValidator(object $validator): static
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Проверка валидности ссылки
     *
     * @param Entry $entry
     *
     * @return bool
     */
    protected function isEntryValidation(Entry $entry): bool
    {
        return !$this->validator || $this->validator->validate($entry);
    }
}