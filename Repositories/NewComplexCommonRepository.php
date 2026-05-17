<?php

namespace NewCMS\Repositories;

use TRMEngine\Exceptions\TRMMySqlQueryException;
use TRMEngine\Repository\TRMDataObjectsContainerRepository;

/**
 * с 2018.07.08 - основной класс хранилища для работы с продуктом вмсесте со вспомогательными объектами
 *
 * @author TRM
 *
 * @template T of \TRMEngine\DataObject\Interfaces\TRMDataObjectsContainerInterface
 * @extends TRMDataObjectsContainerRepository<T>
 */
abstract class NewComplexCommonRepository extends TRMDataObjectsContainerRepository
{
public function doUpdate($ClearCollectionFlag = true)
{
    $TransactionStarted = false;

    try {
        $TransactionStarted = (bool)$this->getDataSource()->beginTransaction();

        $this->deleteAllRelation();
        parent::doUpdate($ClearCollectionFlag);

        if ($TransactionStarted) {
            if (!$this->getDataSource()->commitTransaction()) {
                throw new TRMMySqlQueryException(__METHOD__ . " Не удалось подтвердить транзакцию.");
            }
        }
    } catch (\Throwable $Exception) {
        if ($TransactionStarted) {
            $this->getDataSource()->rollbackTransaction();
        }

        if ($Exception instanceof TRMMySqlQueryException) {
            throw $Exception;
        }

        throw new TRMMySqlQueryException(
            __METHOD__ . " Ошибка транзакционного обновления комплексного объекта: " . $Exception->getMessage(),
            0,
            $Exception
        );
    }
}

/**
 * для комплексного товара перед обновлением 
 * удаляются все данные дочерних коллекций из БД.
 * 
 * @return void
 */
protected function deleteAllRelation()
{
    if (empty($this->CollectionToUpdate) || !$this->CollectionToUpdate->count()) { return; }
    // массив с Id-товаров в обновляемой коллекции
    $IdsArray = array();
/**
 * @var array $TablesNames - массив с именами таблиц и индексных полей, 
 * содержащих связи МНОГОЕ-КО-МНОГОМУ для дочерних коллекций:
 * array( TableName, IdFieldNAme )
 */
    $TablesNames = array();
    // получаем первый объект из коллекции,
    // подразумевается, что репозиторий работает с однотипными объектами,
    // и у всех одинаковый набор дочерних коллекций
    $this->CollectionToUpdate->rewind();
    $DataObject = $this->CollectionToUpdate->current();
    if (!$DataObject) { return; }
    // получаем массив всех дочерних коллекций для очередного Complex объекта $DataObject
    $ChildArr = $DataObject->getChildCollectionsArray();
    // если пуст, т.е. дочерних коллекций нет, то завершаем
    if( empty($ChildArr) ) { return; }

    // теперь перебираем все дочерние коллекции для текущего объекта $DataObject
    foreach( $ChildArr as $ChildCollectionObject )
    {
        // тип объектов хранимых в коллекции
        $CurrentObjectType = $ChildCollectionObject->getObjectsType();
        // получаем имя таблицы и имя поля для значения родительского ID
        $ParentIdFieldName = $CurrentObjectType::getParentIdFieldName();
        // сохраняем в массив $TablesNames
        $TablesNames[$ParentIdFieldName[0]] = $ParentIdFieldName[1];
    }

    // перебираем все Complex объекты добавленные в коллекцию для обновления
    foreach( $this->CollectionToUpdate as $DataObject )
    {
        // получаем ID для самого родительского объекта, он же текущий $DataObject
        $Id = $DataObject->getId();
        // если $Id === null , то это новый товар, для него ничего не удаляем
        if( $Id !== null )
        {
            // индекс совпадает со значением ID
            $IdsArray[$Id] = $Id;
        }
    }
    if(empty($IdsArray)) { return; }
    $DeleteQuery = "";
    $IdsStr = implode(",", $IdsArray);
    foreach( $TablesNames as $TableName => $IdFieldName )
    {
        $DeleteQuery .= "DELETE FROM `{$TableName}` WHERE `{$IdFieldName}` IN ({$IdsStr});";
    }
    $this->getDataSource()->getDBObject()->multiQuery($DeleteQuery);
}


} // NewComplexCommonRepository
