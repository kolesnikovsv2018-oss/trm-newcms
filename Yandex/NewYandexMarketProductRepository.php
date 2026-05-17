<?php

namespace NewCMS\Yandex;

use NewCMS\Repositories\NewLiteProductRepository;
use NewCMS\Repositories\NewRepository;
use NewCMS\Yandex\NewYandexMarketProduct;
use TRMEngine\DataMapper\TRMDataMapper;
use TRMEngine\DataSource\Interfaces\TRMDataSourceInterface;

/**
 * Репозиторий для работы с товарами из прайса на Яндекс.Маркет
 *
 * @date 2019-10-26
 */
class NewYandexMarketProductRepository extends NewRepository
{
  static protected $DataObjectMap = array(
    "new_yandex_market_products" => array(
      TRMDataMapper::STATE_INDEX => TRMDataMapper::FULL_ACCESS_FIELD,
      TRMDataMapper::FIELDS_INDEX => array(
        "ID_Price" => array(
          TRMDataMapper::KEY_INDEX => "UNI",
        ),
      ),
    ),
  );


  public function __construct(TRMDataSourceInterface $DataSource)
  {
    parent::__construct(NewYandexMarketProduct::class, $DataSource);
  }


  /**
   * товар с Id есть в таблице товаров для YandexMarket вернет true в JSON,
   * иначе false
   * 
   * @param int|string|null $ProductId
   * @return bool
   */
  public function existsProduct($ProductId): bool
  {
    if ($ProductId === null) {
      return false;
    }

    $YandexMarketProduct = $this->getOneBy("new_yandex_market_products", "ID_Price", intval($ProductId));

    if (!$YandexMarketProduct) {
      return false;
    }

    if (!$YandexMarketProduct instanceof NewYandexMarketProduct) {
      return false;
    }

    if ($YandexMarketProduct->getData("new_yandex_market_products", "ID_Price") == $ProductId) {
      return true;
    }
    return false;
  }
  /**
   * Добавляет товар с $ProductId в таблицу товаров для YandexMarket
   * 
   * @param int|string|null $ProductId - ID добавляемого товара
   * 
   * @return bool
   */
  public function addProduct($ProductId): bool
  {
    if ($ProductId === null) {
      return false;
    }

    $YandexMarketProduct = $this->getNewObject();
    if (!$YandexMarketProduct instanceof NewYandexMarketProduct) {
      return false;
    }

    $YandexMarketProduct->setData("new_yandex_market_products", "ID_Price", intval($ProductId));
    $this->insert($YandexMarketProduct);
    try {
      $this->doInsert();
    } catch (\Exception $e) {
      echo json_encode(array("Code" => $e->getCode(), "Message" => $e->getMessage()));
      exit;
    }
    return true;
  }

  /**
   * Удаляет товар с Id из таблицы товаров для YandexMarket
   * 
   * @param int|string|null $ProductId - ID удаляемого товара
   * 
   * @return bool
   */
  public function removeProduct($ProductId): bool
  {
    if ($ProductId === null) {
      return false;
    }

    try {
      $YandexMarketProduct = $this->getOneBy("new_yandex_market_products", "ID_Price", intval($ProductId));
      if (
        !$YandexMarketProduct
        || !$YandexMarketProduct instanceof NewYandexMarketProduct
        || $YandexMarketProduct->getData("new_yandex_market_products", "ID_Price") != $ProductId
      ) {
        return true;
      }
      $this->delete($YandexMarketProduct);
      $this->doDelete();
      return true;
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * @param int $GroupId - ID-группы для которой формируется YML,
   * по умолчанию 0 - будет сформирован для всех
   * 
   * @return array|null - массив с ID-товаров, которые есть в таблице 
   * для формирования YML для Маркета, если таких нет, вернется null
   */
  public function getIdArray(int $GroupId = 0): ?array
  {
    $this->setOrderField("new_yandex_market_products.ID_Price");
    if ($GroupId) {
      $IdStr = implode(
        ", ",
        NewLiteProductRepository::getProductsIdFromDB(
          $this->DataSource->getDBObject(),
          $GroupId,
          false,
          false
        )
          ->getDataArray()
      );
      if (empty($IdStr)) {
        return null;
      }
      $this->addCondition("new_yandex_market_products", "ID_Price", $IdStr, "IN");
    }

    $Collection = $this->getAll();
    if (!$Collection) {
      return null;
    }
    $ResArr = array();
    foreach ($Collection as $Object) {
      $ResArr[] = $Object["new_yandex_market_products"]["ID_Price"];
    }
    if (empty($ResArr)) {
      return null;
    }

    return $ResArr;
  }
} // NewYandexMarketProductRepository
