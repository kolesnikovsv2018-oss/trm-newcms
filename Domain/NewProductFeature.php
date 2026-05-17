<?php

namespace NewCMS\Domain;

/**
 * класс для коллекций характеристик товаров
 */
class NewProductFeature extends NewFeaturesCollection
{
/**
 * @var array - массив = (имя объекта, имя свойства) содержащего Id родителя в коллекции,
 * должен определяться в каждом дочернем классе со своими именами
 */
static protected $ParentIdFieldName = array( "goodsfeatures", "ID_Price" );


} // NewProductFeature
