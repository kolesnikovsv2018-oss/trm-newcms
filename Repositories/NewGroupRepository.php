<?php

namespace NewCMS\Repositories;

use NewCMS\Domain\NewGroup;
use NewCMS\Libs\NewHelper;
use NewCMS\Repositories\Exceptions\NewGroupWrongNumberException;
use TRMEngine\DataArray\TRMDataArray;
use TRMEngine\DataMapper\TRMDataMapper;
use TRMEngine\DataObject\Interfaces\TRMDataObjectInterface;
use TRMEngine\DataObject\Interfaces\TRMDataObjectsCollectionInterface;
use TRMEngine\DataSource\Interfaces\TRMDataSourceInterface;
use TRMEngine\DataSource\TRMSqlDataSource;
use TRMEngine\Exceptions\TRMMySqlQueryException;
use TRMEngine\TRMMySqlObject\TRMMySqlObject;

//******************************************************************************
// класс для объекта группа товаров , одно из свойств - ссылка на объект родителя, может быть ноль!!!
//******************************************************************************
class NewGroupRepository extends NewIdTranslitRepository
{
  static protected $DataObjectMap = array(
    "group" => array(
      TRMDataMapper::STATE_INDEX => TRMDataMapper::FULL_ACCESS_FIELD,
      TRMDataMapper::FIELDS_INDEX => array(
        "ID_group" => array(
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
   * @var integer - номер родительской группы,  при выборке методом getAll вернутся дочерние подгруппы
   */
  protected $CurrentGroupId = null;
  /**
   * @var boolean - флаг, указывающий на необходимость собирать в коллекцию подгруппы подгрупп (рекурсивно)
   */
  protected $SubGroupsFlag = false;
  /**
   * @var boolean - флаг, указывающий на необходимость собирать всю информацию из родительских групп
   */
  protected $FullParentInfoFlag = false;
  /**
   *
   * @var bool - если не false, будут выбираться только группы,
   * у кторых present=1
   */
  protected $PresentFlag = false;


  public function __construct(TRMDataSourceInterface $DataSource)
  {
    parent::__construct(NewGroup::class, $DataSource);
  }

  /**
   * @return boolean - флаг, указывающий на необходимость собирать всю информацию из родительских групп
   */
  public function getFullParentInfoFlag()
  {
    return $this->FullParentInfoFlag;
  }
  /**
   * @param boolean $FullParentInfoFlag - флаг, 
   * указывающий на необходимость собирать всю информацию из родительских групп
   */
  public function setFullParentInfoFlag($FullParentInfoFlag = true)
  {
    $this->FullParentInfoFlag = $FullParentInfoFlag;
  }

  /**
   * устанавливает условие для флага present при выборке из БД,
   * поумолчанию выбираются все записи не зависимо от значения present,
   * после установки, до первой выборки, будет работать только это значение, 
   * оно обнулится, как и все условия, после выбоки из БД функцией getAll или getOne
   * 
   * @param integer $present - значеине флага
   */
  public function setPresentFlagCondition($present = 1)
  {
    $this->PresentFlag = true;
    $this->addCondition("group", "GroupPresent", $present);
  }



  /**
   * задает сортировку коллекции при выборке из БД по дополнительному полю в дополнение к стандвртному набору
   * 
   * @param string $FieldName - имя поля по которому нужно сортировать спислк групп, 
   * если передано одно поле GroupOrder, то порядок его сортировки изменится на новое значение
   * @param boolean $AscFlag - если true - по возрастанию, 0 - по убыванию
   * @param int $FieldQuoteFlag - флаг, указывающий на необходимость заключать сортируемые поля в кавычки
   * 
   * @return void
   */
  public function setOrderBy($FieldName = "", $AscFlag = true, $FieldQuoteFlag = TRMSqlDataSource::NEED_QUOTE)
  {

    if ($FieldName == "GroupOrder") {
      $this->DataSource->setOrderField($FieldName, $AscFlag);
      return;
    }
    // очистка значений сортировки
    $this->DataSource->clearOrder();

    if (!empty($FieldName)) {
      $this->DataSource->setOrderField($FieldName, $AscFlag, $FieldQuoteFlag);
    }
    $this->DataSource->setOrderField("GroupOrder");
  }

  /**
   * @return boolean - возвращает флаг, 
   * указывающий на необходимость собирать в коллекцию все из группы из подгрупп, 
   * и далее из подгрупп подгрупп
   */
  public function getSubGroupsFlag()
  {
    return $this->SubGroupsFlag;
  }
  /**
   * @param boolean $SubGroupsFlag - флаг, 
   * указывающий на необходимость собирать в коллекцию все из группы из подгрупп, 
   * и далее из подгрупп подгрупп
   */
  public function setSubGroupsFlag($SubGroupsFlag = true)
  {
    $this->SubGroupsFlag = $SubGroupsFlag;
  }

  /**
   * устанавливает родительскую группу для коллекции 
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
   * @return integer - текущая родительская группа для коллекции, если задана, либо null
   */
  public function getCurrentGroupId()
  {
    return $this->CurrentGroupId;
  }

  /**
   * если установлен SubGroupsFlag, то формируется список всех дочерних групп, и их подгрупп...
   * список включает саму группу CurrentGroupId,
   * иначе только дочерние группы первого уровня 
   * для родительской группы CurrentGroupId, 
   * добавляет условие в SQL-запрос к базе
   */
  protected function generateSubGroupStr()
  {
    if ($this->CurrentGroupId === null) {
      $this->SubGroupsStr = "";
      return;
    }
    if ($this->SubGroupsFlag) {
      // проверку empty($IdGroupArray) не делаем, 
      // getAllChildsArray(...) должна вернуть массив хотя бы из одного элемента,
      // так как $this->CurrentGroupId не null !!!
      $IdGroupArray = self::getSubGroupsIdFromDB(
        $this->DataSource->getDBObject(),
        $this->CurrentGroupId,
        $this->PresentFlag,
        true
      );
      //        NewHelper::getAllChildsArray( 
      //                $this->CurrentGroupId, 
      //                "group", 
      //                "ID_group", 
      //                "GroupID_Parent", 
      //                "GroupOrder", 
      //                $this->PresentFlag ? "GroupPresent" : null,
      //                false
      //            );
      $this->SubGroupsStr = implode(",", $IdGroupArray->getDataArray());
      if (!empty($this->SubGroupsStr)) {
        $this->addCondition("group", "ID_group", $this->SubGroupsStr, "IN");
      }
    } else {
      $this->SubGroupsStr = "";
      $this->addCondition("group", "GroupID_Parent", $this->CurrentGroupId);
    }
  }

  /**
   * кроме очистки основных параметров запроса
   * так же очищает значение стартовой группы,
   * устанавливает флаг сбора подгрупп в FALSE и очищает строку с подгруппами
   */
  public function clearQueryParams()
  {
    parent::clearQueryParams();
    $this->CurrentGroupId = null;
    $this->SubGroupsFlag = false;
    $this->SubGroupsStr = "";
    $this->PresentFlag = false;
  }

  /**
   * получаем данные коллекции из БД, 
   * добавляются условия для сбора коллекции подгрупп по CurrentGroupId
   * 
   * @param TRMDataObjectsCollectionInterface $Collection
   * 
   * @return TRMDataObjectsCollectionInterface
   */
  public function getAll(TRMDataObjectsCollectionInterface $Collection = null)
  {
    // генерирует и добавляет 
    // либо условие для поиска всех груп, для которых родителем является CurrentGroupId,
    // либо, если установлен SubGroupsFlag, собирает все дочерние группы,
    // а так же подгруппы подгрупп и т.д. и добавляет их в условие
    $this->generateSubGroupStr();

    // получает коллекцию групп из БД
    // после getAll() очищаются все параеметры WHERE запроса
    // так же получает всех родителей, 
    // если установлен FullParentInfoFlag...
    return parent::getAll($Collection);
  }

  /**
   * 
   * @param array $DataArray
   * @param TRMDataObjectInterface $DataObject
   * @return NewGroup
   */
  protected function getDataObjectFromDataArray(array &$DataArray, TRMDataObjectInterface $DataObject = null): TRMDataObjectInterface
  {
    $NewDataObject = parent::getDataObjectFromDataArray($DataArray, $DataObject);
    if (!$NewDataObject instanceof NewGroup) {
      return $NewDataObject;
    }

    // если собирать информацию из родительских групп не надо, 
    // то возвращаем объект
    if (!$this->FullParentInfoFlag) {
      return $NewDataObject;
    }
    // если полное название группы включает родительскую часть и есть родитель, 
    // то получаем родительскую группу из БД
    if ($NewDataObject["group"]["GroupID_parent"] && $NewDataObject["group"]["ParentGroupTitle"]) {
      // в родительском методе TRMIdDataObjectRepository::getById
      // выполнится проверка на наличе объекта с таким Id в контейнере репозитория
      // если объекта с ID не надется, то последует новый запрос к DataSource,
      $NewDataObject->setParentGroupObject(
        $this->getById($NewDataObject["group"]["GroupID_parent"])
      );
      $NewDataObject->generateGroupFullTitle();
    }
    return $NewDataObject;
  }

  /**
   * если у группы поле GroupPresent устанвлено в 0 или FALSE,
   * то после обновления у всех дочерних товаров Present тоже устанавливается в 0,
   * обновляются дочерние товары всех групп из подготовленной коллекции CollectionToUpdate
   * 
   * @param bool $ClearCollectionFlag - если нужно после обновления сохранить коллекцию обновленных объектов, 
   * то этот флаг следует утсановить в false, это может понадобиться дочерним методам,
   * но перед завершением дочернего doUpdate нужно очистить коллекцию,
   * что бы не повторять обновление в будущем 2 раза!
   */
  public function doUpdate($ClearCollectionFlag = true)
  {
    $TransactionStarted = false;
    $GroupsToHideProducts = array();

    try {
      $TransactionStarted = (bool)$this->DataSource->beginTransaction();

      /**
       * Определяем группы, для которых нужно выполнить каскадный сброс present у товаров.
       *
       * Важно: каскад должен срабатывать только при реальном переходе GroupPresent из 1 в 0,
       * а не при любом обновлении группы, где GroupPresent уже равен 0
       * (например, при инкременте GroupVisits во время обычного просмотра страницы).
       */
      foreach ($this->CollectionToUpdate as $DataObject) {
        $CurrentIdGroup = isset($DataObject["group"]["ID_group"]) ? (int)$DataObject["group"]["ID_group"] : 0;
        if ($CurrentIdGroup <= 0) {
          continue;
        }

        if ($this->shouldCascadeHideProducts($DataObject, $CurrentIdGroup)) {
          $GroupsToHideProducts[] = $CurrentIdGroup;
        }
      }

      $GroupsToHideProducts = array_values(array_unique($GroupsToHideProducts));

      parent::doUpdate(false);

      foreach ($GroupsToHideProducts as $CurrentIdGroup) {
        // Каскадно скрываем товары только для групп с подтвержденным переходом 1 -> 0.
        $query = "UPDATE `table1` SET `present`=0 WHERE `Group`={$CurrentIdGroup}";
        $this->DataSource->executeQuery($query);
      }

      if ($ClearCollectionFlag) {
        $this->CollectionToUpdate->clearCollection();
      }

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
        __METHOD__ . " Ошибка транзакционного обновления группы: " . $Exception->getMessage(),
        0,
        $Exception
      );
    }
  }

  /**
   * Проверяет, нужен ли каскадный сброс present у товаров группы.
   *
   * Каскад выполняется только при переходе GroupPresent: 1 -> 0.
   * Если GroupPresent не менялся или уже был 0 до обновления,
   * каскад НЕ выполняется.
   *
   * @param TRMDataObjectInterface $DataObject
   * @param int $CurrentIdGroup
   * @return bool
   */
  protected function shouldCascadeHideProducts(TRMDataObjectInterface $DataObject, $CurrentIdGroup)
  {
    if (!isset($DataObject["group"]) || !is_array($DataObject["group"])) {
      return false;
    }

    if (!array_key_exists("GroupPresent", $DataObject["group"])) {
      return false;
    }

    $NewPresent = $this->normalizePresentFlagValue($DataObject["group"]["GroupPresent"]);

    // Каскад нужен только когда новое значение явно falsy (0).
    if ($NewPresent !== false) {
      return false;
    }

    $OldPresent = $this->fetchCurrentGroupPresentFlag($CurrentIdGroup);

    // Срабатываем только при реальном переходе 1 -> 0.
    return $OldPresent === true;
  }

  /**
   * Возвращает текущий GroupPresent из БД для указанной группы.
   *
   * Чтение выполняется прямым SQL-запросом, чтобы не зависеть от identity-map
   * и получить фактическое сохраненное значение до parent::doUpdate().
   *
   * @param int $CurrentIdGroup
   * @return bool
   * @throws TRMMySqlQueryException
   */
  protected function fetchCurrentGroupPresentFlag($CurrentIdGroup)
  {
    $Result = $this->DataSource->executeQuery(
      "SELECT `GroupPresent` FROM `group` WHERE `ID_group`=" . (int)$CurrentIdGroup . " LIMIT 1"
    );

    if (!$Result || !$Result->num_rows) {
      throw new TRMMySqlQueryException(__METHOD__ . " Группа не найдена: ID_group=" . (int)$CurrentIdGroup);
    }

    $Row = $Result->fetch_row();
    if (!$Row || !array_key_exists(0, $Row)) {
      throw new TRMMySqlQueryException(__METHOD__ . " Не удалось прочитать GroupPresent для ID_group=" . (int)$CurrentIdGroup);
    }

    return $this->normalizePresentFlagValue($Row[0]);
  }

  /**
   * Нормализует значение флага present/groupPresent к bool.
   *
   * @param mixed $Value
   * @return bool
   */
  protected function normalizePresentFlagValue($Value)
  {
    if ($Value === null) {
      return false;
    }

    $StringValue = strtolower(trim((string)$Value));
    if ($StringValue === "" || $StringValue === "0" || $StringValue === "false" || $StringValue === "no") {
      return false;
    }

    return true;
  }

  /**
   * 
   * @param TRMMySqlObject $DBO
   * @param int $GroupId - ID родительской (стартовой) группы
   * @param boolean $PresentFlag - если указан флаг присутсвия (по умолчанию), будут выбираться только ID,
   * в записи которых поле GroupPresent не пустое
   * @param boolean $AddParenIdFlag - если установлен в true (по умолчанию), то 
   * в результирующий массив будет включен ID родителя ($GroupId)
   * 
   * @return TRMDataArray - возвращает массив с ID дочерних групп и $GroupId,
   * если $AddParenIdFlag === true
   */
  public static function getSubGroupsIdFromDB(TRMMySqlObject $DBO, $GroupId, $PresentFlag = true, $AddParenIdFlag = true)
  {
    return NewHelper::getAllChildsArray(
      $DBO,
      $GroupId,
      "group",
      "ID_group",
      "GroupID_Parent",
      "GroupOrder",
      $PresentFlag ? "GroupPresent" : null,
      $AddParenIdFlag
    );
  }
} // NewGroupRepository
