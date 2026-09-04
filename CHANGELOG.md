# Changelog

## [2.0.0] - 2026-09-04

### Changed

- Зависимость: `trm/trmengine ^2.0` (EngineConfig: параметры debug/fastcgi/
  error-log вместо константы DEBUG и getenv в движке).
- **Configuration API**: `NewCmsExtension` + `NewCmsConfig` — параметры
  host-приложения вместо чтения файлов пакетом; 134 потребителя переведены
  с `GlobalConfig::$ConfigArray` на `NewCmsConfig::current()`
  (dual-mode: легаси-хранилище заполняется из параметров).
- Host-константы `ROOT`/`TOPIC`/`PAGE_NUMERIC_NAME` удалены из кода пакета
  (theme-пути — `NewCMSPathResolver`, project root и пагинация — Config).
- `Libs/MapData` перемещены в PSR-4-локацию `MapData/`.

### Removed

- Чтение файлов конфигурации пакетом (config-файл — обязанность host);
  `autoload.files` сохраняет только `GlobalConfig.php` (легаси-хранилище)
  до финала 2.0.

## [1.0.5] - 2026-09-04

### Fixed

- Корзина: поддержка дробного количества (метраж м² на карточках комплектов).
  Кнопка «Купить» на карточке комплекта (например,
  `potolok-grilyato-100x100x40-mm-b10-belyj-matovyj-a903-ekonom`) передаёт
  количество `0.36`, которое отбрасывалось:
  - `js/basket.js`: `parseInt` → `parseFloat` (с заменой запятой);
  - `Libs/NewBasket.php`: `(int)` → `floatval` при распаковке и упаковке cookie;
  - `Libs/NewBasketProduct.php`: `(int)` → `float`.
  Баг существовал с канонического тега 1.0.0 («main - new js»): старая копия
  basket.js валидацию количества не имела.

## [1.0.4] - 2026-09-04

### Fixed

- PSR-4-полнота: «вторичные» классы, определённые в чужих файлах (наследие
  classmap), вынесены в собственные файлы — `Domain/Exceptions/*`
  (`NewComplectZeroPartPriceException`, `NewComplectWrongQueryException`,
  `NewArticles*`, `NewProducts*`), `Libs/Logger/Exceptions/*`,
  `Widgets/GroupCrumbs`, `Widgets/ArticleCrumbs`. Под PSR-4-автозагрузкой
  Composer часть из них вызывала `Class not found` (например, страница
  `/price`).
- `Libs/MapData/NewMapDataObject*` перемещены в PSR-4-локацию `MapData/`
  (namespace `NewCMS\MapData`), удалены неработающие compatibility-алиасы
  `NewCMS\Libs\MapData`.

### Added

- `tools/check-classes.php` — gate полноты PSR-4 (все референсы
  `TRMEngine\*`/`NewCMS\*` обязаны иметь собственный файл); подключён в CI.

## [1.0.3] - 2026-09-03

### Added

- Ограничение по времени («time limit») для корзины:
  `Controllers/NewBasketController.php`, `js/basketpage.js`.

### Changed

- composer.json: удалено поле `version` (версии — Git-теги), добавлены
  `type: library`, `license`, `autoload-dev`, `config.sort-packages`.
- Добавлены `.gitignore`, CI-workflow GitHub Actions, PHPUnit-тесты.

Примечание: `Libs/GlobalConfig.php` остаётся в `autoload.files` до релиза 2.0
(удаление глобальной конфигурации — часть миграции на configuration API).

## [1.0.2] - 2026-05-17

### Fixed

- Пути к view виджетов через `NewCMSPathResolver` (совместимость с
  composer-установкой в vendor).

## [1.0.1] - 2026-05-17

### Fixed

- Обработка null-ответов меню в `js/catalogtree2.js`.

## [1.0.0] - 2026-05-17

### Added

- Первая общая версия CMS-пакета: переработка JS (error handler,
  конфигурируемый `TopicPath`), рефакторинг внешних путей
  (`NewCMSPathResolver`).
