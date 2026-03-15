<?php

namespace Sholokhov\Sitemap\ORM;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\SystemException;

/**
 * Временные данные в процессе генерации карты сайта
 */
class RuntimeTable extends DataManager
{
    public const PC_ID = "ID";
    public const PC_PROCESSED = "PROCESSED";

    public const PC_PID = "PID";
    public const PC_STATUS = "STATUS";
    public const PC_ITEM_ID = "ITEM_ID";
    public const PC_TYPE = "ITEM_TYPE";

    public static function getTableName(): string
    {
        return "sholokhov_sitemap_runtime";
    }

    public static function getMap(): array
    {
        return [
            new Fields\IntegerField(self::PC_ID)
                ->configurePrimary()
                ->configureAutocomplete(),

            new Fields\BooleanField(self::PC_PROCESSED),

            new Fields\StringField(self::PC_PID)
                ->configureSize(255)
                ->configureRequired(),

            new Fields\StringField(self::PC_STATUS)
                ->configureSize(255),

            new Fields\StringField(self::PC_ITEM_ID),

            (new Fields\StringField(self::PC_TYPE))
                ->configureSize(255)
        ];
    }

    /**
     * Удаление всех данных связанных с элементом
     *
     * @param int $id
     *
     * @return void
     * @throws ArgumentException
     * @throws SqlQueryException
     * @throws SystemException
     */
    public static function clearByItemID(int $id): void
    {
        $sql = sprintf('DELETE FROM %s WHERE %s="%s";', self::getTableName(), self::PC_ITEM_ID, $id);
        self::getEntity()->getConnection()->query($sql);
    }

    /**
     * Очистка таблицы по PID
     *
     * @param string $pid
     *
     * @return void
     */
    public static function clearByPid(string $pid): void
    {
        $sql = sprintf('DELETE FROM %s WHERE %s="%s";', self::getTableName(), self::PC_PID, $pid);
        self::getEntity()->getConnection()->query($sql);
    }
}