<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;

class sholokhov_sitemap extends CModule
{
    var $MODULE_ID = "sholokhov.sitemap";
    var $PARTNER_NAME = 'Шолохов Даниил';
    var $PARTNER_URI = 'https://github.com/sholokhov-daniil';

    private const PHP_VERSION = '8.4.0';

    public function __construct()
    {
        $arModuleVersion = [];

        include(__DIR__ . DIRECTORY_SEPARATOR . "version.php");
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        } else {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage("SHOLOKHOV_EXCHANGE_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("SHOLOKHOV_EXCHANGE_MODULE_DESCRIPTION");
    }

    public function DoInstall(): bool
    {
        global $APPLICATION;

        try {
            $this->checkPhpVersion();
            $this->InstallDB();
        } catch (Throwable $exception) {
            $APPLICATION->ThrowException($exception->getMessage());
            return false;
        }

        return true;
    }

    public function DoUninstall(): void
    {
        $this->UnInstallDB();
    }

    public function InstallDB(): void
    {
        $this->registrationEvents();
        $this->Add();

        self::IncludeModule($this->MODULE_ID);

        \Sholokhov\Sitemap\ORM\RuntimeTable::getEntity()->createDbTable();
    }

    public function UnInstallDB(): void
    {
        $this->unRegistrationEvents();
        $this->Remove();
    }

    private function checkPhpVersion(): void
    {
        if (version_compare(phpversion(), self::PHP_VERSION) == -1) {
            throw new Exception(
                Loc::getMessage("SHOLOKHOV_EXCHANGE_MODULE_INVALID_PHP", ['#VERSION#' => self::PHP_VERSION])
            );
        }
    }

    private function registrationEvents(): void
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandlerCompatible("main", "OnBeforeProlog", $this->MODULE_ID);
    }

    private function unRegistrationEvents(): void
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler("main", "OnBeforeProlog", $this->MODULE_ID);
    }
}