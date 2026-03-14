<?php

namespace Sholokhov\Sitemap\Pipeline;

use Sholokhov\Sitemap\Entry;
use Sholokhov\Sitemap\Validator\ValidatorInterface;

trait UseValidatorTrait
{
    /**
     * Валидатор добавляемых ссылок
     *
     * @var ValidatorInterface[]
     */
    protected array $validators = [];

    /**
     * Добавление валидатора сохраняемой ссылки
     *
     * @param ValidatorInterface $validator
     * @return $this
     */
    public function addValidator(ValidatorInterface $validator): static
    {
        $this->validators[] = $validator;
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
        foreach ($this->validators as $validator) {
            if (!$validator->validate($entry)) {
                return false;
            }
        }

        return true;
    }
}