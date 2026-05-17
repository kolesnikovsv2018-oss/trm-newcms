<?php

namespace NewCMS\Repositories;

use InvalidArgumentException;
use TRMEngine\DataMapper\TRMDataMapper;
use TRMEngine\DataMapper\TRMSafetyFields;
use TRMEngine\DataSource\Interfaces\TRMDataSourceInterface;
use TRMEngine\Repository\TRMIdDataObjectRepository;

/**
 * с 2018.07.28 - основной класс хранилища для работы с продуктом из таблицы table1 без вспомогательных объектов,
 * но с описанием из отдельной таблицы - goodsdescription и единицами измерения - unit
 *
 * @author TRM
 */
abstract class NewRepository extends TRMIdDataObjectRepository
{
/**
 * @param string $objectclassname - имя класса для объектов, за которые отвечает этот Repository
 */
public function __construct($objectclassname, TRMDataSourceInterface $DataSource)
{
    parent::__construct($objectclassname, $DataSource);

    $this->initializeDataMapper($DataSource);
}

/**
 * @return array
 */
protected function getDataObjectMap(): array
{
    $Reflection = new \ReflectionClass(static::class);
    $DataObjectMap = $Reflection->getStaticPropertyValue('DataObjectMap');
    if (!is_array($DataObjectMap)) {
        throw new InvalidArgumentException(static::class . ': invalid DataObjectMap root.');
    }

    return $DataObjectMap;
}

/**
 * Инициализирует DataMapper для текущего репозитория на основе static::$DataObjectMap.
 *
 * @param TRMDataSourceInterface $DataSource
 * @return void
 */
protected function initializeDataMapper(TRMDataSourceInterface $DataSource)
{
    $DataObjectMap = $this->getDataObjectMap();
    $this->validateDataObjectMap($DataObjectMap);

    $SafetyFields = new TRMSafetyFields($DataSource->getDBObject());
    $SafetyFields->setFieldsArray($DataObjectMap, TRMDataMapper::READ_ONLY_FIELD);
    $SafetyFields->completeSafetyFieldsFromDB();
    $SafetyFields->getIdFieldName();

    $this->DataMapper = $SafetyFields;
}

/**
 * Легковесная валидация конфигурации static::$DataObjectMap до обращения к БД.
 *
 * Проверяет:
 * - структуру таблиц и полей,
 * - допустимые значения индекса Key,
 * - корректность relation-целей (таблица/поле).
 *
 * @param array $DataObjectMap
 * @return void
 */
protected function validateDataObjectMap(array $DataObjectMap)
{
    if (empty($DataObjectMap)) {
        throw new InvalidArgumentException(static::class . ': invalid DataObjectMap root.');
    }

    $KnownFieldsByObject = array();

    foreach ($DataObjectMap as $ObjectName => $ObjectState) {
        if (!is_string($ObjectName) || '' === $ObjectName) {
            throw new InvalidArgumentException(static::class . ': invalid object name in DataObjectMap.');
        }
        if (!is_array($ObjectState)) {
            throw new InvalidArgumentException(static::class . ": object '{$ObjectName}' state must be array.");
        }
        if (!isset($ObjectState[TRMDataMapper::FIELDS_INDEX]) || !is_array($ObjectState[TRMDataMapper::FIELDS_INDEX]) || empty($ObjectState[TRMDataMapper::FIELDS_INDEX])) {
            throw new InvalidArgumentException(static::class . ": object '{$ObjectName}' has empty or missing Fields section.");
        }

        $KnownFieldsByObject[$ObjectName] = array_keys($ObjectState[TRMDataMapper::FIELDS_INDEX]);

        foreach ($ObjectState[TRMDataMapper::FIELDS_INDEX] as $FieldName => $FieldState) {
            if (!is_string($FieldName) || '' === $FieldName) {
                throw new InvalidArgumentException(static::class . ": object '{$ObjectName}' contains invalid field name.");
            }
            if (!is_array($FieldState)) {
                continue;
            }

            if (isset($FieldState[TRMDataMapper::KEY_INDEX])) {
                $KeyValue = (string)$FieldState[TRMDataMapper::KEY_INDEX];
                if (!in_array($KeyValue, array('', 'PRI', 'UNI', 'MUL'), true)) {
                    throw new InvalidArgumentException(
                        static::class . ": object '{$ObjectName}', field '{$FieldName}' has invalid Key value '{$KeyValue}'."
                    );
                }
            }
        }
    }

    foreach ($DataObjectMap as $ObjectName => $ObjectState) {
        foreach ($ObjectState[TRMDataMapper::FIELDS_INDEX] as $FieldName => $FieldState) {
            if (!is_array($FieldState) || !isset($FieldState[TRMDataMapper::RELATION_INDEX])) {
                continue;
            }

            $Relation = $FieldState[TRMDataMapper::RELATION_INDEX];
            if (!is_array($Relation)) {
                throw new InvalidArgumentException(
                    static::class . ": object '{$ObjectName}', field '{$FieldName}' has non-array Relation."
                );
            }

            if (!isset($Relation[TRMDataMapper::OBJECT_NAME_INDEX]) || !isset($Relation[TRMDataMapper::FIELD_NAME_INDEX])) {
                throw new InvalidArgumentException(
                    static::class . ": object '{$ObjectName}', field '{$FieldName}' relation must contain ObjectName and FieldName."
                );
            }

            $TargetObject = (string)$Relation[TRMDataMapper::OBJECT_NAME_INDEX];
            $TargetField = (string)$Relation[TRMDataMapper::FIELD_NAME_INDEX];

            if (!isset($KnownFieldsByObject[$TargetObject])) {
                throw new InvalidArgumentException(
                    static::class . ": object '{$ObjectName}', field '{$FieldName}' relation points to unknown object '{$TargetObject}'."
                );
            }

            if (!in_array($TargetField, $KnownFieldsByObject[$TargetObject], true)) {
                throw new InvalidArgumentException(
                    static::class . ": object '{$ObjectName}', field '{$FieldName}' relation points to unknown field '{$TargetObject}.{$TargetField}'."
                );
            }
        }
    }
}


} // NewLiteProductRepository
