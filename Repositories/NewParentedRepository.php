<?php

namespace NewCMS\Repositories;

use TRMEngine\DataMapper\TRMSafetyFields;
use TRMEngine\DataObject\Interfaces\TRMDataObjectInterface;
use TRMEngine\DataObject\Interfaces\TRMDataObjectsCollectionInterface;
use TRMEngine\DataSource\Interfaces\TRMDataSourceInterface;
use TRMEngine\TRMMySqlObject\Exceptions\TRMMySqlQueryException;
use TRMEngine\Repository\TRMParentedDataObjectRepository;

/**
 * Общий контроллер для коллекций, соединенных таблицей МНОГОЕ-КО-МНОГОМУ,
 * изменены методы Update, перед обновлением 
 * данные из таблицы МНОГОЕ-КО-МНОГОМУ должны сначала удаляться,
 * а потом нужно записывать новые (обновленные) данные
 *
 * @author TRM 2019-05-07
 */
abstract class NewParentedRepository extends TRMParentedDataObjectRepository
{
  /**
   * @var bool - если этот флаг установлен в true, то перед добавлением данной 
   * дочерней коллекции из связывающей таблицы будут удалены все старые связи 
   * по ID-родителя
   */
  protected $ClearAllRelationBeforeUpdateFlag = true;

  public function __construct($objectclassname, TRMDataSourceInterface $DataSource)
  {
    parent::__construct($objectclassname, $DataSource);

    $Reflection = new \ReflectionClass(static::class);
    $DataObjectMap = $Reflection->getStaticPropertyValue('DataObjectMap');

    $SafetyFields = new TRMSafetyFields($DataSource->getDBObject());
    $SafetyFields->setFieldsArray($DataObjectMap);
    $SafetyFields->completeSafetyFieldsFromDB();
    $SafetyFields->sortObjectsForRelationOrder(true);

    $this->setDataMapper($SafetyFields);
  }

  /**
   * для соединяющей таблицы МНОГОЕ-КО-МНОГОМУ данные не обновляются,
   * старые должны полностью удаляться из таблицы для всех ID-родителя,
   * а обновляемые объекты добавляются в коллекцию вставляемых CollectionToInsert
   * 
   * @param TRMDataObjectInterface $DataObject
   */
  public function update(TRMDataObjectInterface $DataObject)
  {
    parent::insert($DataObject);
  }
  /**
   * для соединяющей таблицы МНОГОЕ-КО-МНОГОМУ данные не обновляются,
   * старые должны полностью удаляться из таблицы для всех ID-родителя,
   * а обновляемые объекты добавляются в коллекцию вставляемых CollectionToInsert
   * 
   * @param TRMDataObjectsCollectionInterface $Collection
   */
  public function updateCollection(TRMDataObjectsCollectionInterface $Collection)
  {
    parent::insertCollection($Collection);
  }
  /**
   * для соединяющей таблицы МНОГОЕ-КО-МНОГОМУ данные не обновляются,
   * старые должны полностью удаляться из таблицы для всех ID-родителя,
   * а обновляемые объекты добавляются в коллекцию вставляемых CollectionToInsert
   * 
   * @param boolean $ClearCollectionFlag
   */
  public function doUpdate($ClearCollectionFlag = true)
  {
    $TransactionStarted = false;

    try {
      $TransactionStarted = (bool)$this->DataSource->beginTransaction();

      if ($this->ClearAllRelationBeforeUpdateFlag) {
        $this->deleteAllRelationsForParents();
      }
      parent::doInsert($ClearCollectionFlag);

      if ($TransactionStarted) {
        if (!$this->DataSource->commitTransaction()) {
          throw new TRMMySqlQueryException(__METHOD__ . " Не удалось подтвердить транзакцию.");
        }
      }
    } catch (\Throwable $Exception) {
      if ($TransactionStarted) {
        $this->DataSource->rollbackTransaction();
      }

      if ($Exception instanceof TRMMySqlQueryException) {
        throw $Exception;
      }

      throw new TRMMySqlQueryException(
        __METHOD__ . " Ошибка транзакционного обновления связей: " . $Exception->getMessage(),
        0,
        $Exception
      );
    }
  }

  /**
   * для комплексного объекта перед обновлением 
   * удаляются все данные дочерних коллекций из БД.
   * 
   * @throws TRMMySqlQueryException
   */
  private function deleteAllRelationsForParents()
  {
    $DeleteQuery = "";
    $ProcessedParentIds = array();

    foreach ($this->CollectionToInsert as $DataObject) {
      $ParentDataObject = $DataObject->getParentDataObject();
      if (null === $ParentDataObject) {
        continue;
      }

      $ParentId = $ParentDataObject->getId();
      if (null === $ParentId || "" === $ParentId) {
        continue;
      }

      $ParentIdFieldName = $DataObject::getParentIdFieldName();
      $TableName = $ParentIdFieldName[0];
      $IdFieldName = $ParentIdFieldName[1];

      $ParentKey = $TableName . "|" . $IdFieldName . "|" . (string)$ParentId;
      if (isset($ProcessedParentIds[$ParentKey])) {
        continue;
      }
      $ProcessedParentIds[$ParentKey] = true;

      $DeleteQuery .= "DELETE FROM `{$TableName}` WHERE `{$IdFieldName}`="
        . $this->makeSqlScalarValue($ParentId)
        . ";";
    }

    if (!empty($DeleteQuery)) {
      $this->DataSource->completeMultiQuery($DeleteQuery);
    }
  }

  /**
   * Formats scalar values for direct SQL query parts in deleteAllRelationsForParents.
   *
   * @param mixed $value
   * @return string
   */
  private function makeSqlScalarValue($value)
  {
    if (is_int($value) || is_float($value)) {
      return (string)$value;
    }

    if (is_bool($value)) {
      return $value ? "1" : "0";
    }

    return "'" . addslashes((string)$value) . "'";
  }


} // NewParentedRepository
