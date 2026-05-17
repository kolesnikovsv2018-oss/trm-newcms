<?php

namespace NewCMS\Domain;

use NewCMS\DataObjects\NewIdTranslitDataObject;
use NewCMS\Domain\Exceptions\NewProductsExceptions;
use NewCMS\Libs\NewHelper;
use NewCMS\Libs\TRMValuta;

/**
 * класс для работы с продуктом из таблицы table1 без вспомогательных объектов
 * 2018-07-28
 *
 * @author TRM
 */
class NewLiteProduct extends NewIdTranslitDataObject
{
  /**
   * @var array - имя свойства для идентификатора объекта, обычно совпадает с именем ID-поля из БД
   */
  static protected $IdFieldName = array("table1", "ID_price");
  /**
   * @var array - имя свойства с названием или заголовком объекта, обычно совпадает с полем name или title из таблицы БД
   */
  static protected $TitleFieldName = array("table1", "Name");

  /**
   * @var array - имя свойства с транскрипцией названия на английском - используется для URL товара, группы или другого документа
   */
  static protected $TranslitFieldName = array("table1", "PriceTranslit");

  public function setData(string $objectname, string $fieldname, mixed $value): void
  {
    if (
      $objectname === "goodsdescription"
      && $fieldname === "ID_goods"
      && empty($value)
    ) {
      // если передано пустое значение для ID описания товара,
      // то устанавливаем NULL, чтобы БД сама проставила автоинкремент
      $value = null;
    }

    if (
      $objectname === "table1"
      && empty($value)
    ) {
      // отключил этот код, так как setData вызывается не только при создании объекта,
      // но так же  и при получении данных из БД, и там могут быть пустые значения
      // // TODO - нужно сделать проверку, например - validateSettingData,
      // // чтобы обязательные поля не оставались пустыми
      // if ($fieldname === "valuta") {
      //   // если не задана валюта товара, то выбрасываем исключение
      //   throw new NewProductsExceptions("Не задана валюта товара!" . $this->getId(), 503);
      // }

      if ($fieldname === "Prestige") {
        // если не задано значение престижа, то по умолчанию устанавливаем 0
        $value = 0;
      }

      // TODO - подумать над correctValue, и вызывать в родительском методе
      if (
        $fieldname === "ID_price"
        || $fieldname === "order_time"
        || $fieldname === "ParentId"
      ) {
        // если эти поля не заданы, то устанавливаем NULL,
        // чтобы БД сама проставила автоинкремент
        $value = null;
      }
    }

    parent::setData($objectname, $fieldname, $value);
  }

  /**
   * устанавливает для объекта значение ID-поля первичного ключа!!!
   * для этого первичный ключ должен быт задан в getIdFieldName()
   * так же устанавливает это значение для поля ID_goods
   *
   * @param mixed $id - ID-объекта
   */
  public function setId(int|string|null $id): void
  {
    if (empty($id)) {
      // если передано пустое значение, то устанавливаем NULL
      // чтобы БД сама проставила автоинкремент
      $id = null;
    }
    parent::setId($id);
    $this->setData("goodsdescription", "ID_goods", $id);
  }

  /**
   * обнуляет ID-объекта и поля ID_goods
   * эквивалентен setId(null);
   */
  public function resetId(): void
  {
    parent::resetId();
    $this->setData("goodsdescription", "ID_goods", null);
  }

  /**
   * устанавливает базовую (начальную) цену товара и вычисляет 3 цены с наценками
   * 
   * @param double $Price0 - начальная цена в валюте товара
   */
  public function setPrice0($Price0)
  {
    $this->setData("table1", "price0", $Price0);
    $this->setData("table1", "PriceRUB", TRMValuta::convert($Price0, $this->getData("table1", "valuta")));
    NewHelper::setPrices($this, $this->getData("table1", "PriceRUB"));
  }

  /**
   * @return array - массив array(Price1, Price2, Price3), индекс начинается с 0
   */
  public function getPriceArray()
  {
    return array(
      $this->getData("table1", "Price1"),
      $this->getData("table1", "Price2"),
      $this->getData("table1", "Price3")
    );
  }

  /**
   * проверяет критические данные товара (имя, родительская группа),
   * если они не установлены, то выбрасывается исключение
   * 
   * @throws NewProductsExceptions
   */
  public function validate()
  {
    if (!$this->getData("table1", "Group")) {
      throw new NewProductsExceptions("Не установлена родительская группа!", 503);
    }
    if (!$this->getData("table1", "Name")) {
      throw new NewProductsExceptions("Не задано название товара!", 503);
    }
    if (!$this->getData("table1", "PriceTranslit")) {
      $this->translit();
    }
  }
} // NewLiteProduct