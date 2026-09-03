<?php

namespace NewCMS\Libs;

use NewCMS\Domain\NewLiteProductForCollection;

/**
 * класс для хранения единицы товара в корзине
 * ссылка на сам товар из БД и его количество
 */
class NewBasketProduct
{
/**
 * @var NewLiteProductForCollection - объект товара
 */
public $Item;
/**
 * @var float - количество товаров ($Item) в корзине (может быть дробным — метраж м2)
 */
public $Count;

function __construct($id, $count)
{
    $this->Item = new NewLiteProductForCollection();
    $this->Item->setId($id);
    $this->Count = max(0, (float)str_replace(',', '.', (string)$count));
}

} // BasketGoods
