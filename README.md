# Генератор карты сайта для 1c-bitrix

**Данный модуль еще находится на этапе разработки**

## Источники данных

### Файловая система

Производит рекурсивный обход файловой системы, и возвращает все доступные ссылки на файлы.  

>
> Поисе директорий и файлов производится через метод битрикса `CSeoUtils::getDirStructure`, который в обязательной мере требует наличие файла .section.php и явной установки заголовка `$APPLICATION->SetTitle('...')`
>

```php
use Sholokhov\Sitemap\Source\IO\IOSource;

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
``` 

### Разделы инфоблока

Производит рекурсивный обход дерева инфоблока с учетом элементов. В рамках оптимизации процесса, обход производится пошагово, что снижает затраты на ОЗУ, но увеличивает общее время формирования карты сайта.

```php
use Sholokhov\Sitemap\Settings\IBlock\IBlockItem;
use Sholokhov\Sitemap\Source\IBlock\SectionSource;

$source = new SectionSource(
    // Настройки индексации инфоблока
    new IBlockItem(
        // ID инфоблока по которому будет организован обход
        id: 2,
        // ID разделов исключенных из обхода
        executedSections: []
        // ID элементов исключенных из обхода
        executedSectionElements: []
    ),
    // ID раздела с которого идет поиск
    0,
);
```

### Элементы инфоблока

Производит обход раздела инфоблока в рамках одного уровня. В рамках оптимизации процесса, обход производится пошагово, что снижает затраты на ОЗУ, но увеличивает общее время формирования карты сайта.

```php
use Sholokhov\Sitemap\Settings\IBlock\IBlockItem;
use Sholokhov\Sitemap\Source\IBlock\ElementSource;

$source = new ElementSource(
    // ID родительского раздела элементов
    23,

    // Настройки индексации инфоблока
    new IBlockItem(
        // ID инфоблока по которому будет организован обход
        id: 2,
        // ID разделов исключенных из обхода
        executedSections: []
        // ID элементов исключенных из обхода
        executedSectionElements: []
    ),
);
```

## Валидаторы ссылок

### robots.txt

Валидатор проверяет отсутствия блокировки ссылки в файле robots.txt. Ссылка считается заблокированной при наличии подходящего `Disallow`. На основе ID сайта определяется путь до корня, и парсится файл.

```php
use Sholokhov\Sitemap\Validator\RobotsValidator;

$validator = new RobotsValidator(siteId: 's1');
$validator->validate($entry);
```

### .htaccess

Парсится файл .htaccess, для поиска редиректов, если для ссылки был найден редирект, то она исключается из карты сайта.
На основе ID сайта определяется путь до корня, и парсится файл.

```php
use Sholokhov\Sitemap\Validator\HtaccessValidator;

$validator = new HtaccessValidator(siteId: 's1');
$validator->validate($entry);
```

### HTTP

Производит HTTP запрос на каждую страницу, которая планируется добавиться в карту сайта. На основе ответа определяется необходимость добавлять в карту.

```php
use Sholokhov\Sitemap\Validator\HttpValidator;

$validator = new HttpValidator();
$validator->validate($entry);
```

#### Статус ответа
Проверяет статус ответа от `200` до `299`. Если статус ответа входит в данный интервал, то ссылка пропускается.

#### Canonical
Если на странице отсутствует canonical или не соответствует проверяемой страницы, то ссылка не проходит валидацию.



## Генератор карты сайта

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