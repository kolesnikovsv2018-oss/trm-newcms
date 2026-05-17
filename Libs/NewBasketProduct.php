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
 * @var int - количество товаров ($Item) в корзине
 */
public $Count;

function __construct($id, $count)
{
    $this->Item = new NewLiteProductForCollection();
    $this->Item->setId($id);
    $this->Count = max(0, (int)$count);
}

} // BasketGoods
