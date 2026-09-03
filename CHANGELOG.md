# Changelog

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
