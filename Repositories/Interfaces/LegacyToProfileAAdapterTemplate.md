# Legacy -> Profile A Adapter Template

Назначение: безопасный шаблон миграции legacy-репозитория (Profile L) в DataSource-oriented профиль (Profile A)
без изменения бизнес-логики read/write методов.

## 1. Когда применять

1. Класс наследуется напрямую от TRMRepository/TRMIdDataObjectRepository.
2. В классе локально инициализируется TRMSafetyFields.
3. Нужно привести класс к единому DI-профилю NewRepository/NewIdTranslitRepository.

## 2. Пошаговый шаблон миграции

### Шаг 1: создать базовый адаптер-класс

```php
<?php

namespace NewCMS\Repositories\Adapters;

use NewCMS\Repositories\NewRepository;
use TRMEngine\DataSource\Interfaces\TRMDataSourceInterface;

abstract class NewLegacyProfileAAdapter extends NewRepository
{
    public function __construct($objectClassName, TRMDataSourceInterface $DataSource)
    {
        parent::__construct($objectClassName, $DataSource);
    }

    // Переопределять при необходимости в наследнике,
    // если legacy-класс использует нестандартный mapper-bootstrap.
    protected function initializeDataMapper(TRMDataSourceInterface $DataSource)
    {
        parent::initializeDataMapper($DataSource);
    }
}
```

### Шаг 2: перевести legacy-класс на адаптер

1. Заменить базовый класс legacy-репозитория на NewLegacyProfileAAdapter или NewRepository.
2. Сохранить constructor signature на TRMDataSourceInterface.
3. Перенести нестандартную mapper-инициализацию в override initializeDataMapper(...), если требуется.

### Шаг 3: сохранить бизнес-методы без изменений

1. Не менять SQL/фильтры/условия в read/write методах на этом шаге.
2. Менять только DI/mapper-bootstrap слой.

### Шаг 4: проверки

1. php -l по измененным классам.
2. Smoke проверка целевых read/write сценариев.
3. Отчет в reports с фиксацией, что бизнес-поведение не изменено.

## 3. Применение к текущим legacy-классам

1. site/web/libs/Logger/NewGuestVisitedRepository.php
2. site/web/libs/MapData/NewMapDataObjectRepository.php

## 4. Ограничения

1. Транзакции/целостность не решаются на этом шаге (это зона TRMSqlDataSource).
2. Миграция выполняется малыми пакетами по одному legacy-классу.
