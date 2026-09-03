<?php

namespace NewCMS\Repositories;

use NewCMS\Domain\NewLiteProductForCollection;
use NewCMS\Repositories\Exceptions\NewGroupWrongNumberException;
use TRMEngine\DataMapper\TRMDataMapper;
use TRMEngine\DataMapper\Interfaces\TRMDataMapperReadModelInterface;
use TRMEngine\DataObject\Interfaces\TRMDataObjectsCollectionInterface;
use TRMEngine\DataSource\Interfaces\TRMDataSourceInterface;
use TRMEngine\DataSource\Interfaces\TRMDataSourceSelectQueryBuilderInterface;
use TRMEngine\DataSource\TRMSqlDataSource;
use TRMEngine\TRMMySqlObject\Exceptions\TRMMySqlQueryException;

/**
 * с 2018.07.15 - основной класс для работы с хранилищем коллекции товаров
 *
 * @version 2019.03.24
 */
class NewLiteProductForCollectionRepository extends NewLiteProductRepository
{
  static protected $DataObjectMap = array(
    "table1" => array(
      TRMDataMapper::STATE_INDEX => TRMDataMapper::FULL_ACCESS_FIELD,
      TRMDataMapper::FIELDS_INDEX => array(
        "ID_price" => array(
          TRMDataMapper::KEY_INDEX => "PRI",
        ),
        "unit" => array(
          // из unit будет выбрана запись 
          // (только одна запись из unit для каждой из table1 так как ID_unit - уникален!!!), 
          // для которой `unit`.`ID_unit` === `table1`.`unit`
          TRMDataMapper::RELATION_INDEX => array(
            TRMDataMapper::OBJECT_NAME_INDEX => "unit",
            TRMDataMapper::FIELD_NAME_INDEX => "ID_unit"
          ),
        ),
        "vendor" => array(
          TRMDataMapper::RELATION_INDEX => array(
            TRMDataMapper::OBJECT_NAME_INDEX => "vendors",
            TRMDataMapper::FIELD_NAME_INDEX => "ID_vendor"
          ),
        ),
      )
    ),
    "unit" => array(
      TRMDataMapper::STATE_INDEX => TRMDataMapper::READ_ONLY_FIELD,
      TRMDataMapper::FIELDS_INDEX => array(
        "ID_unit" => array(
          TRMDataMapper::KEY_INDEX => "PRI",
        ),
      ),
    ),
    "vendors" => array(
      TRMDataMapper::STATE_INDEX => TRMDataMapper::READ_ONLY_FIELD,
      TRMDataMapper::FIELDS_INDEX => array(
        "ID_vendor" => array(
          TRMDataMapper::KEY_INDEX => "PRI",
        ),
      ),
    ),
  );

  /**
   * @var string - строка с номерами дочерних групп через запятую для использования в запросах
   */
  protected $SubGroupsStr = "";
  /**
   * @var string - строка с номерами товаров (ID_price) среди которых производится выборка
   */
  protected $IdPriceStr = "";
  /**
   * @var array - массив характеристик, которым должен удовлетворять список
   */
  protected $FeaturesList = array();
  /**
   * @var integer - номер стартовой группы для всех товаров и подкатегорий, в том числе для выборки по характеристикам
   */
  protected $CurrentGroupId = null;
  /**
   * @var boolean - флаг, указывающий на необходимость собирать в коллекцию товаров из подгрупп
   */
  protected $SubGroupsFlag = true;


  public function __construct(TRMDataSourceInterface $DataSource)
  {
    // Intentional bypass of NewLiteProductRepository::__construct():
    // this repository has its own DataObject class/map and must initialize mapper via NewRepository.
    NewRepository::__construct(NewLiteProductForCollection::class, $DataSource);

    $this->DataMapper->removeField("table1", "Description");

    $this->setOrderBy();
  }

  public function clearCondition()
  {
    parent::clearCondition();
    $this->IdPriceStr = "";
    $this->CurrentGroupId = null;
    $this->FeaturesList = array();
    $this->SubGroupsStr = "";
  }

  /**
   * устанавливает условие для флага present при выборке из БД,
   * поумолчанию выбираются все записи не зависимо от значения present,
   * после установки, до первой выборки, будет работать только это значение, 
   * оно обнулится, как и все условия, после выбоки из БД функцией getAll или getOne
   * 
   * @param integer $present - значение флага 1 или 0
   */
  public function setPresentFlagCondition($present = 1)
  {
    $this->addCondition("table1", "present", $present);
  }

  /**
   * задает сортировку коллекции при выборке из БД по дополнительному полю в дополнение к стандвртному набору
   * 
   * @param string $FieldName - имя поля по которому нужно сортировать, 
   * если не задано, то в сортировку добавляется только стандартный набор полей 
   * ( (CASE WHEN `price0` =0 THEN 1 ELSE 0 END),  [$FieldName  ,] item_order, price0, Group, Name ),
   * если передано одно из стандартных полей, то порядок его сортировки изменится на новое значение
   * 
   * @param boolean $AscFlag - если true - по возрастанию, 0 - по убыванию
   */
  public function setOrderBy($FieldName = "", $AscFlag = true, $FieldQuoteFlag = TRMSqlDataSource::NEED_QUOTE)
  {

    if ($FieldName == "Group" || $FieldName == "item_order") {
      $this->DataSource->setOrderField($FieldName, $AscFlag);
      return;
    }
    // очистка значений сортировки
    $this->DataSource->clearOrder();
    // товары без цен всегда отображаются в конце
    // у комплектов все цены нулевые, поэтому пока комментируем
    //    $this->DataSource->setOrderField(
    //        "(CASE WHEN `price0` =0 THEN 1 ELSE 0 END)", 
    //        true, 
    //        TRMSqlDataSource::NOQUOTE
    //    );

    if (!empty($FieldName)) {
      $this->DataSource->setOrderField($FieldName, $AscFlag, $FieldQuoteFlag);
    }
    $this->DataSource->setOrderField("Group");
    $this->DataSource->setOrderField("item_order");
  }

  /**
   * @return boolean - возвращает флаг, указывающий на необходимость собирать в коллекцию товары из подгрупп
   */
  public function getSubGroupsFlag()
  {
    return $this->SubGroupsFlag;
  }
  /**
   * @param boolean $SubGroupsFlag - флаг, указывающий на необходимость собирать в коллекцию товары из подгрупп
   */
  public function setSubGroupsFlag($SubGroupsFlag = true)
  {
    $this->SubGroupsFlag = $SubGroupsFlag;
  }

  /**
   * устанавливает группу начиная с которой собираем список товаров, 
   * так же формируется список всех дочерних групп, и новый SQL-запрос к базе
   *
   * @param int $id - номер группы (категории) из таблицы БД для товара
   */
  public function setCurrentGroupId($id)
  {
    if ($id === null) {
      throw new NewGroupWrongNumberException(__METHOD__);
    }
    $this->CurrentGroupId = intval($id);
  }

  /**
   * @return integer - текущая группа для коллекции товаров, если задана, либо null
   */
  public function getCurrentGroupId()
  {
    return $this->CurrentGroupId;
  }

  /**
   * формируется список всех дочерних групп, добавляет условие в SQL-запрос к базе
   */
  protected function generateSubGroupStr()
  {
    if ($this->CurrentGroupId === null) {
      $this->SubGroupsStr = "";
      return;
    }

    if ($this->SubGroupsFlag) {
      // проверку empty($IdGroupArray) не делаем, 
      // getSubGroupsIdFromDB(...) должна вернуть массив хотя бы из одного элемента,
      // так как $this->CurrentGroupId не null !!!
      $IdGroupArray = NewGroupRepository::getSubGroupsIdFromDB(
        $this->DataSource->getDBObject(),
        $this->CurrentGroupId,
        true
      );
      $this->SubGroupsStr = implode(",", $IdGroupArray->getDataArray());
    } else {
      $this->SubGroupsStr = (string)$this->CurrentGroupId;
    }

    $this->addCondition("table1", "Group", $this->SubGroupsStr, "IN");
  }

  /**
   * устанавливается массив со списком характеристик
   *
   * @param array $FeaturesList - двумерный массив характеристик из array( 0 => array( "id" , "value" ), 1 => array(...), ... )
   */
  public function setFeaturesList(array $FeaturesList = null)
  {
    if (!isset($FeaturesList) || empty($FeaturesList)) {
      $this->FeaturesList = array();
      $this->IdPriceStr = '';
      return true;
    }
    $this->FeaturesList = $FeaturesList;
  }

  /**
   * @return array - возвращает двумерный массив характеристик из array( "id" , "value" )
   */
  public function getFeaturesList()
  {
    return $this->FeaturesList;
  }

  /**
   * добавляет условие для SQL-запроса 
   * со списком номеров товаров удовлетворяющих заданным характеристикам, 
   *
   * @return integer - возвращает количество полученных ID-товаров удовлетворяющих установленным характеристикам, 
   * 0 - если ничего не нашлось
   */
  protected function generateFeaturesSQL(): int
  {
    $FeaturesList = $this->FeaturesList;
    $this->IdPriceStr = "";
    if (empty($FeaturesList)) {
      return 0;
    }

    $query = $this->buildFeaturesSelectQuery($FeaturesList);

    $result = $this->DataSource->executeQuery($query);

    if (!$result) {
      throw new TRMMySqlQueryException($query);
    }
    if (!$result->num_rows) {
      return 0;
    }
    $IdPriceList = array();
    while ($row = $result->fetch_row()) {
      if (isset($row[0])) {
        $IdPriceList[] = (int)$row[0];
      }
    }

    if (empty($IdPriceList)) {
      return 0;
    }

    $IdPriceList = array_values(array_unique($IdPriceList));

    $this->IdPriceStr = implode(",", $IdPriceList);

    if (!empty($this->IdPriceStr)) {
      $this->addCondition("table1", "ID_price", $this->IdPriceStr, "IN");
    }

    return $result->num_rows;
  }

  /**
   * Собирает SQL для выборки ID товаров по списку характеристик.
   *
   * @param array $FeaturesList
   * @return string
   */
  protected function buildFeaturesSelectQuery(array $FeaturesList): string
  {
    $FeaturesList = $this->normalizeFeaturesListForQuery($FeaturesList);
    if (empty($FeaturesList)) {
      return "SELECT `ID_Price` FROM `goodsfeatures` WHERE 1=0";
    }

    $FeaturesById = $this->groupFeaturesById($FeaturesList);
    $Conditions = $this->buildFeatureConditionsById($FeaturesById);
    $RequiredFeaturesCount = count($FeaturesById);

    return $this->composeIdPriceIntersectionQuery($Conditions, $RequiredFeaturesCount);
  }

  /**
   * Строит SQL-условия для каждой группы ID_Feature.
   *
   * @param array<int, array<int, string>> $FeaturesById
   * @return array<int, string>
   */
  protected function buildFeatureConditionsById(array $FeaturesById): array
  {
    $Conditions = array();

    foreach ($FeaturesById as $FeatureId => $Values) {
      $ValuesSql = $this->buildSqlStringList($Values);
      $Conditions[] = "`ID_Feature` = {$FeatureId} AND `FeaturesValue` IN ({$ValuesSql})";
    }

    return $Conditions;
  }

  /**
   * Связывает условия по характеристикам через пересечение ID_Price.
   *
   * @param array<int, string> $Conditions
   * @return string
   */
  protected function composeIdPriceIntersectionQuery(array $Conditions, int $RequiredFeaturesCount): string
  {
    if (empty($Conditions) || $RequiredFeaturesCount <= 0) {
      return "SELECT `ID_Price` FROM `goodsfeatures` WHERE 1=0";
    }

    $WhereClause = $this->composeFeaturesWhereClause($Conditions);

    // Для каждой характеристики допускается несколько значений (OR),
    // а наличие всех характеристик обеспечивается через HAVING COUNT(DISTINCT ID_Feature).
    $query = "SELECT `ID_Price` FROM `goodsfeatures`"
      . " WHERE {$WhereClause}"
      . " GROUP BY `ID_Price`"
      . " HAVING COUNT(DISTINCT `ID_Feature`) = {$RequiredFeaturesCount}";

    return $query;
  }

  /**
   * Собирает WHERE-часть для feature-фильтра из условий по ID_Feature.
   *
   * @param array<int, string> $Conditions
   * @return string
   */
  protected function composeFeaturesWhereClause(array $Conditions): string
  {
    if (empty($Conditions)) {
      return "1=0";
    }

    return "(" . implode(") OR (", $Conditions) . ")";
  }

  /**
   * Группирует нормализованный список характеристик по ID характеристики.
   *
   * @param array $FeaturesList
   * @return array<int, array<int, string>>
   */
  protected function groupFeaturesById(array $FeaturesList): array
  {
    $Grouped = array();

    foreach ($FeaturesList as $Feature) {
      $FeatureId = intval($Feature["id"]);
      if (!isset($Grouped[$FeatureId])) {
        $Grouped[$FeatureId] = array();
      }
      $Grouped[$FeatureId][] = (string)$Feature["value"];
    }

    return $Grouped;
  }

  /**
   * Собирает SQL-список строковых литералов: 'v1','v2',...
   *
   * @param array $Values
   * @return string
   */
  protected function buildSqlStringList(array $Values): string
  {
    $Escaped = array();
    foreach ($Values as $Value) {
      $Escaped[] = "'" . $this->escapeSqlString((string)$Value) . "'";
    }

    return implode(",", $Escaped);
  }

  /**
   * Нормализует список характеристик для детерминированной SQL-сборки:
   * группирует по id, сортирует id по возрастанию, сохраняет порядок значений внутри id.
   *
   * @param array $FeaturesList
   * @return array
   */
  protected function normalizeFeaturesListForQuery(array $FeaturesList): array
  {
    $ById = array();
    $SeenValuesById = array();
    foreach ($FeaturesList as $feature) {
      if (!is_array($feature) || !isset($feature["id"]) || !array_key_exists("value", $feature)) {
        continue;
      }

      $id = intval($feature["id"]);
      if ($id <= 0) {
        continue;
      }

      if (!isset($ById[$id])) {
        $ById[$id] = array();
        $SeenValuesById[$id] = array();
      }

      $valueKey = (string)$feature["value"];
      if (isset($SeenValuesById[$id][$valueKey])) {
        continue;
      }
      $SeenValuesById[$id][$valueKey] = true;

      $ById[$id][] = array(
        "id" => $id,
        "value" => $feature["value"],
      );
    }

    if (empty($ById)) {
      return array();
    }

    foreach ($ById as &$Items) {
      usort($Items, function ($a, $b) {
        return strcmp((string)$a["value"], (string)$b["value"]);
      });
    }
    unset($Items);

    ksort($ById, SORT_NUMERIC);

    $Normalized = array();
    foreach ($ById as $Items) {
      foreach ($Items as $Item) {
        $Normalized[] = $Item;
      }
    }

    return $Normalized;
  }

  /**
   * Экранирует строковое значение для ручных SQL-условий.
   *
   * @param string $value
   * @return string
   */
  protected function escapeSqlString(string $value): string
  {
    $DBObject = $this->DataSource->getDBObject();
    if ($DBObject && isset($DBObject->newlink) && $DBObject->newlink) {
      return $DBObject->newlink->real_escape_string($value);
    }

    return addslashes($value);
  }

  /**
   * Строит count-запрос из текущего makeSelectQuery(DataMapper).
   *
   * @return string
   */
  protected function makeCountQueryFromCurrentSelect(): string
  {
    $SelectQuery = $this->makeSelectQueryForDataSource($this->DataSource);
    $CountQuery = $this->convertSelectToCountQuery($SelectQuery);

    if (!empty($CountQuery)) {
      return $CountQuery;
    }

    throw new TRMMySqlQueryException(__METHOD__ . " Не удалось преобразовать SELECT в COUNT-запрос.");
  }

  /**
   * Преобразует SELECT-запрос в COUNT-запрос без ORDER/LIMIT/OFFSET.
   * Возвращает null, если преобразование не удалось.
   *
   * @param string $SelectQuery
   * @return string|null
   */
  protected function convertSelectToCountQuery(string $SelectQuery): ?string
  {
    // Сначала удаляем ORDER/LIMIT/OFFSET, затем применяем единую regex для преобразования.
    $CleanedQuery = $this->stripOrderLimitOffsetClause($SelectQuery);
    $CountQuery = $this->applyCountTransformation($CleanedQuery);

    if (!empty($CountQuery) && $CountQuery !== $CleanedQuery) {
      return $CountQuery;
    }

    return null;
  }

  /**
   * Удаляет ORDER BY, LIMIT, и OFFSET из конца SELECT-запроса.
   *
   * @param string $SelectQuery
   * @return string
   */
  protected function stripOrderLimitOffsetClause(string $SelectQuery): string
  {
    // Регулярное выражение для удаления ORDER BY/LIMIT/OFFSET в конце.
    $Stripped = preg_replace(
      "/(?:ORDER\s+BY.*?|LIMIT\s+\d+(?:\s+OFFSET\s+\d+)?|OFFSET\s+\d+)(?:\s|$)/iUs",
      " ",
      $SelectQuery
    );

    // Очищаем лишние пробелы в конце.
    return trim($Stripped);
  }

  /**
   * Применяет трансформацию SELECT в COUNT-запрос.
   * Ожидает SELECT...FROM без ORDER/LIMIT/OFFSET.
   *
   * @param string $SelectQuery
   * @return string|null
   */
  protected function applyCountTransformation(string $SelectQuery): ?string
  {
    // Простая regex: SELECT ... FROM ... → SELECT count(`ID_price`) FROM ...
    $CountQuery = preg_replace(
      "/^SELECT\s+(.+?)\s+FROM\s+(.+)$/iUs",
      "SELECT count(`ID_price`) FROM $2",
      $SelectQuery
    );

    if (!empty($CountQuery) && $CountQuery !== $SelectQuery) {
      return $CountQuery;
    }

    return null;
  }

  /**
   * Безопасно вызывает makeSelectQuery у DataSource-реализации.
   *
   * @param TRMDataSourceInterface $DataSource
   * @return string
   */
  protected function makeSelectQueryForDataSource(TRMDataSourceInterface $DataSource)
  {
    if (!($DataSource instanceof TRMDataSourceSelectQueryBuilderInterface)) {
      throw new TRMMySqlQueryException(__METHOD__ . " DataSource не поддерживает makeSelectQuery().");
    }
    if (!($this->DataMapper instanceof TRMDataMapperReadModelInterface)) {
      throw new TRMMySqlQueryException(__METHOD__ . " DataMapper не поддерживает read-model контракт.");
    }

    return (string)$DataSource->makeSelectQuery($this->DataMapper);
  }

  /**
   * Строит count-запрос для общего количества товаров по флагу present
   * через DataSource/DataMapper без мутации текущего состояния репозитория.
   *
   * @param int $present
   * @return string
   */
  /**
   * Строит count-запрос для заданного флага present через DataSource/DataMapper.
   * 
   * @param int $present
   * @return string
   */
  protected function makeTotalCountQueryByPresent(int $present = 1): string
  {
    $CountQuery = $this->buildCountSelectFromDataSource($present);
    
    if (!empty($CountQuery)) {
      return $CountQuery;
    }

    throw new TRMMySqlQueryException(__METHOD__ . " Не удалось построить COUNT-запрос для present={$present}");
  }

  /**
   * Строит COUNT-запрос из DataSource с условием present.
   * Гарантирует результат через DataSource-ориентированный подход.
   *
   * @param int $present
   * @return string
   */
  protected function buildCountSelectFromDataSource(int $present): string
  {
    $TmpDataSource = clone $this->DataSource;
    $TmpDataSource->addWhereParam("table1", "present", $present);

    $SelectQuery = $this->makeSelectQueryForDataSource($TmpDataSource);
    $CountQuery = $this->convertSelectToCountQuery($SelectQuery);

    if (!empty($CountQuery)) {
      return $CountQuery;
    }

    // Fallback: гарантируем валидный COUNT-запрос без manual SQL concatenation.
    return $this->buildSafeCountQuery($present);
  }

  /**
   * Строит безопасный COUNT-запрос с явным SQL-структурированием.
   * Используется как fallback когда regex-преобразование недостаточно.
   *
   * @param int $present
   * @return string
   */
  protected function buildSafeCountQuery(int $present): string
  {
    // Используем prepared-style подход (значение типизировано через int приведение).
    $PresentValue = (int)$present;
    return "SELECT count(`ID_price`) FROM `table1` WHERE `present`={$PresentValue}";
  }

  /**
   * @return boolean - если товаров с заданными характеристиками не удалось найти и в случае ошибки вернет false
   */
  protected function generateSubStrings(): bool
  {
    if (empty($this->SubGroupsStr)) {
      $this->generateSubGroupStr();
    }
    if (!empty($this->FeaturesList) && empty($this->IdPriceStr)) {
      if (!$this->generateFeaturesSQL()) {
        return false;
      }
    }
    return true;
  }

  /**
   * получаем данные коллекции из БД
   * 
   * @param TRMDataObjectsCollectionInterface $Collection
   * 
   * @return TRMDataObjectsCollectionInterface
   */
  public function getAll(TRMDataObjectsCollectionInterface $Collection = null)
  {
    if (!$this->generateSubStrings()) {
      return null;
    }
    // получает коллекцию товаров из БД
    // после getAll() очищаются все параеметры WHERE запроса
    // так же получает все цены для товаров, если они являются комплектом, то рекурсивно...
    return parent::getAll($Collection);
  }

  /**
   * получаем количество записей удовлетворяющих запросу из БД
   *
   * @return int|boolean
   */
  public function getProductsCount(): int
  {
    if (!$this->generateSubStrings()) {
      return 0;
    }

    $CountQuery = $this->makeCountQueryFromCurrentSelect();

    $result = $this->DataSource->executeQuery($CountQuery);
    if (!$result) {
      throw new TRMMySqlQueryException($CountQuery);
    }
    $Row = $result->fetch_row();
    if (!$Row || !isset($Row[0])) {
      throw new TRMMySqlQueryException(__METHOD__ . " Пустой результат COUNT-запроса: " . $CountQuery);
    }

    $count = (int)$Row[0];

    return $count;
  }

  /**
   * @return int - возвращает общее кол-во записей в таблице
   */
  public function getTotalCount()
  {
    $CountQuery = $this->makeTotalCountQueryByPresent(1);
    $result = $this->DataSource->executeQuery($CountQuery);
    if (!$result) {
      throw new TRMMySqlQueryException($CountQuery);
    }

    $Row = $result->fetch_row();
    if (!$Row || !isset($Row[0])) {
      throw new TRMMySqlQueryException(__METHOD__ . " Пустой результат COUNT-запроса: " . $CountQuery);
    }

    return (int)$Row[0];
  }
} // NewLiteProductForCollectionRepository
