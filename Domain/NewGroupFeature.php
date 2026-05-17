<?php

namespace NewCMS\Domain;

/**
 * класс для коллекций характеристик групп
 */
class NewGroupFeature extends NewFeaturesCollection
{
/**
 * @var array - массив = (имя объекта, имя свойства) содержащего Id родителя в коллекции,
 * должен определяться в каждом дочернем классе со своими именами
 */
static protected $ParentIdFieldName = array( "groupfeature", "ID_Group" );


} // NewGroupFeature
