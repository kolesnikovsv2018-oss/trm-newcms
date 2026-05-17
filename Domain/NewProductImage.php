<?php

namespace NewCMS\Domain;

use TRMEngine\DataObject\TRMParentedDataObject;

/**
 * с 2018.07.23 - класс для работы с коллекцией дополнительных изображений
 *
 * @author TRM
 */
class NewProductImage extends TRMParentedDataObject
{
  /**
   * @var array - массив = (имя объекта, имя свойства) содержащего Id родителя в коллекции,
   * должен определяться в каждом дочернем классе со своими именами
   */
  static protected $ParentIdFieldName = array("images", "id_good");

  /**
   * записывает данные в конкретную ячейку
   *
   * @param string $objectname - имя sub-объекта, для которого устанавливаются данные
   * @param string $fieldname - имя поля (столбца), в которое производим запись значения
   * @param mixed $value - значение-данные поля 
   */
  public function setData(string $objectname, string $fieldname, mixed $value): void
  {
    if (
      $objectname === "images"
      && $fieldname === "id_image2"
      && empty($value)
    ) {
      // если передано пустое значение для ID изображения,
      // то устанавливаем NULL, чтобы БД сама проставила автоинкремент
      $value = null;
    }
    parent::setData($objectname, $fieldname, $value);
  }
} // NewImagesCollection