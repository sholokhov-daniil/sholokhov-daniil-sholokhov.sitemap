<?php

namespace Sholokhov\Sitemap\Repository;

use Exception;
use Sholokhov\Sitemap\ORM\RuntimeTable;
use Sholokhov\Sitemap\Exception\SitemapStorageException;

use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;

/**
 * Производит хранения данных в таблице {@see RuntimeTable}
 *
 * Описание передаваемых параметров в конструктор:
 *  <li>pid - Отвечает за колонку(свойство) {@see RuntimeTable::PC_PID}</li>
 *  <li>type - Отвечает за колонку(свойство) {@see RuntimeTable::PC_TYPE}</li>
 */
readonly class CacheRuntimeStorage
{
    /**
     * @param string $pid ID ресурса
     * @param string|null $type Тип ресурса
     */
    public function __construct(
        private string $pid,
        private ?string $type = null
    )
    {
    }

    /**
     * Добавление новой записи
     *
     * @param string $id
     * @param string $status
     * @param array $parameters
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SitemapStorageException
     * @throws SystemException
     * @throws Exception
     */
    public function add(string $id, string $status, array $parameters = []): bool
    {
        $defaultParameters = [
            RuntimeTable::PC_PID => $this->getPid(),
            RuntimeTable::PC_ITEM_ID => $id,
            RuntimeTable::PC_PROCESSED => $status,
        ];

        if (null !== $this->type) {
            $defaultParameters[RuntimeTable::PC_TYPE] = $this->type;
        }

        $parameters = array_merge($parameters, $defaultParameters);

        if ($this->exist($id)) {
            $error = sprintf('An entry with ID "%s" already exists', $id);
            throw new SitemapStorageException($error);
        }

        return RuntimeTable::add($parameters)->isSuccess();
    }

    /**
     * Обновление записи
     *
     * @param string $id
     * @param array $parameters
     *
     * @return bool
     * @throws Exception
     */
    public function update(string $id, array $parameters): bool
    {
        $item = $this->getByID($id);
        return RuntimeTable::update($item[RuntimeTable::PC_ID], $parameters)->isSuccess();
    }

    /**
     * Получение записи согласно входным параметрам
     *
     * @param array $parameters
     *
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     * @throws ObjectPropertyException
     */
    public function get(array $parameters): array
    {
        $parameters['filter'][RuntimeTable::PC_PID] = $this->pid;

        if (null !== $this->type) {
            $parameters['filter'][RuntimeTable::PC_TYPE] = $this->type;
        }

        return RuntimeTable::getRow($parameters) ?: [];
    }

    /**
     * Получение записи по ID
     *
     * @param string $id
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getByID(string $id): array
    {
        $parameters = [
            'filter' => [RuntimeTable::PC_ITEM_ID => $id]
        ];

        return $this->get($parameters);
    }

    /**
     * Проверка наличия записи
     *
     * @param string $id
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function exist(string $id): bool
    {
        return !empty($this->getByID($id));
    }

    /**
     * Проверка на наличие игнорирование записи
     *
     * @param string $id
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function isIgnore(string $id): bool
    {
        $parameters = [
            'filter' => [
                '=' . RuntimeTable::PC_ITEM_ID => $id,
                '=' . RuntimeTable::PC_PROCESSED => false,
            ]
        ];

        return !empty($this->get($parameters));
    }

    /**
     * Проверка активности записи
     *
     * @param string $id
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function isActive(string $id): bool
    {
        $parameters = [
            'filter' => [
                '=' . RuntimeTable::PC_ITEM_ID => $id,
                '=' . RuntimeTable::PC_PROCESSED => true,
            ]
        ];

        return !empty($this->get($parameters));
    }

    /**
     * Удаление записи по ID элемента
     *
     * @param string $id
     *
     * @return void
     * @throws ArgumentException
     * @throws SqlQueryException
     * @throws SystemException
     */
    public function delete(string $id): void
    {
        RuntimeTable::clearByItemID($id);
    }

    /**
     * Очистка временных записей
     *
     * @return void
     */
    public function clear(): void
    {
        RuntimeTable::clearByPid($this->getPid());
    }

    /**
     * Возвращает ID ресурса.
     *
     * @return string
     */
    public function getPid(): string
    {
        return $this->pid;
    }

    /**
     * Возвращает тип ресурса.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }
}