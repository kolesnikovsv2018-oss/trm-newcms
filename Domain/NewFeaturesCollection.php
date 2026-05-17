<?php

namespace NewCMS\Domain;

use TRMEngine\DataObject\TRMParentedDataObject;

/**
 * характеристики товаров или группы...
 */
abstract class NewFeaturesCollection extends TRMParentedDataObject
{
/**
 * добавляет значения из другого объекта характеристик, 
 * если только таких характеристик (с таким ID) еще нет в собственном массиве данных
 * 
 * @param NewFeaturesCollection $other - добавляемые значения
 */
public function addFromOtherFeaturesCollection(NewFeaturesCollection $other)
{
    $lookingfieldname = "ID_Feature";
    foreach ($other as $row)
    {
        if( null === $this->getBy( array( $lookingfieldname => $row[$lookingfieldname] ) ) )
        {
            $this->addRow($row);
        }
    }
    $this->changeParentIdForCurrentParent();
}

/**
 * устанавливает значения из другого объекта характеристик удаляя все из собственного массива данных
 * 
 * @param NewFeaturesCollection $other - добавляемые значения
 */
public function setFromOtherFeaturesCollection(NewFeaturesCollection $other)
{
    $this->clear();
    foreach ($other as $row)
    {
        $this->addRow($row);
    }
    $this->changeParentIdForCurrentParent();
}


} // NewFeaturesCollection