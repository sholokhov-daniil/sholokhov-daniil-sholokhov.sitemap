# Генератор карты сайта для 1c-bitrix

Данный модуль еще находится на этапе разработки

## Ручная конфигурация
```php
use Sholokhov\Sitemap\Configuration;
use Sholokhov\Sitemap\Pipeline\Pipeline;
use Sholokhov\Sitemap\Source\IO\IOSource;
use Sholokhov\Sitemap\Generator\SitemapGenerator;

// Получаем конфигурацию генератора карты сайта на основе ID сайта.
$config = Configuration::createFromSiteId(siteId: 's1');
// Инициализируем генератор карты сайта
$generator = new SitemapGenerator(config: $config);

// Добавляем правило сохранения ссылок в файле карты сайта
$generator->addPipeline(
    // Правило сохранения ссылки в определенный файл карты сайта
    new Pipeline(filename: 'sitemap-files.xml')
        // Добавляем источник данных ссылок для файла карты сайта
        ->addSource(
            // Указываем в качестве источника данных файловую систему
            new IOSource(
                siteId: 's1',
                // Указываем какой путь должен индексироваться
                includes: [
                    '/',
                ],
                // Указываем какой путь мы исключаем
                excludes: [
                    '/about/'
                ],
            )
        )
);

// Генерировать карту сайта
$generator->run();
```