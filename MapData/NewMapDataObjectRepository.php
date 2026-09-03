<?php

namespace NewCMS\MapData;

use NewCMS\Repositories\NewRepository;
use TRMEngine\DataMapper\TRMDataMapper;
use TRMEngine\DataMapper\TRMSafetyFields;
use TRMEngine\DataSource\Interfaces\TRMDataSourceInterface;

/**
 * с 2019.11.21 - выбирает из БД только те данные, которые указаны в $DataObjectMap,
 * работает с объектами типа NewMapDataObject
 *
 * @author TRM
 *
 * TODO(C1-step3, DI-migration): legacy Profile L.
 * Целевой профиль: Profile A с адаптерным шаблоном mapper-bootstrap.
 * Требование миграции: сохранить динамический setDataMapperArray(...) без изменения поведения.
 */
class NewMapDataObjectRepository extends NewRepository
{


public function __construct(TRMDataSourceInterface $DataSource)
{
    parent::__construct(NewMapDataObject::class, $DataSource);
}

/**
 * Сохраняет legacy-bootstrap DataMapper для динамической карты setDataMapperArray(...).
 *
 * В этом классе map задается поздно (runtime), поэтому базовая validateDataObjectMap
 * и инициализация через static::$DataObjectMap намеренно не используются.
 *
 * @param TRMDataSourceInterface $DataSource
 * @return void
 */
protected function initializeDataMapper(TRMDataSourceInterface $DataSource)
{
    $this->DataMapper = new TRMSafetyFields($DataSource->getDBObject());
}

/**
 * Устанавливает DataMapper на основе данных из массива $DataObjectMap,
 * который должен иметь вид array( ObjectName1 => array( FieldName1 => array(key => ..., State => ...) ... ) ... )
 * 
 * @param array $DataObjectMap
 * @param int $DefaultState
 */
public function setDataMapperArray(array &$DataObjectMap, $DefaultState = TRMDataMapper::READ_ONLY_FIELD)
{
    $SafetyFields = $this->DataMapper;
    if (!$SafetyFields instanceof TRMSafetyFields) {
        throw new \InvalidArgumentException(get_class($this) . ': DataMapper must be TRMSafetyFields instance');
    }

    $SafetyFields->setFieldsArray($DataObjectMap, $DefaultState);
    $SafetyFields->completeOnlyExistsFieldsFromDB();
    $IDArr = $SafetyFields->getIdFieldName();
    NewMapDataObject::setIdFieldName( $IDArr );
}


} // NewMapDataRepository
