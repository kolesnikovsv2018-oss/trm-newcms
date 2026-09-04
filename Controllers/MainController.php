<?php

namespace NewCMS\Controllers;

use NewCMS\Controllers\BaseController;
use NewCMS\Domain\Exceptions\NewProductsWrongIdExceptions;
use NewCMS\Domain\NewComplexProduct;
use NewCMS\Domain\NewGroup;
use NewCMS\Domain\NewLiteProduct;
use NewCMS\Domain\NewLiteProductForCollection;
use NewCMS\Domain\NewProductFeature;
use NewCMS\Libs\NewHelper;
use NewCMS\Libs\TRMValuta;
use NewCMS\Repositories\Exceptions\NewGroupWrongNumberException;
use NewCMS\Repositories\NewComplexProductRepository;
use NewCMS\Repositories\NewGroupRepository;
use NewCMS\Repositories\NewLiteProductForCollectionRepository;
use NewCMS\Repositories\NewLiteProductRepository;
use NewCMS\Widgets\GroupCrumbs;
use NewCMS\Widgets\NewFeaturesSelector;
use NewCMS\Widgets\NewLastProducts;
use NewCMS\Widgets\NewPagination;
use NewCMS\Widgets\NewPrestigeProducts;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use TRMEngine\DataSource\TRMSqlDataSource;
use TRMEngine\Exceptions\TRMException;
use TRMEngine\Repository\Exceptions\TRMRepositoryNoDataObjectException;

/**
 *  контроллер для отображения групп товаров и самих товаров
 */
class MainController extends BaseController
{
  public function getNewGroupRepository(): NewGroupRepository
  {
    return $this->_RM->getRepository(NewGroup::class);
  }

  public function getNewLiteProductForCollectionRepository(): NewLiteProductForCollectionRepository
  {
    return $this->_RM->getRepository(NewLiteProductForCollection::class);
  }

  public function getComplexRepository(): NewComplexProductRepository
  {
    return $this->_RM->getRepository(NewComplexProduct::class);
  }

  public function getLiteProductRepository(): NewLiteProductRepository
  {
    return $this->_RM->getRepository(NewLiteProduct::class);
  }

  public function actionIndex()
  {
    $From = trim((string)$this->Request->query->get("from", ""));
    $Strprtl = trim((string)$this->Request->query->get("strprtl", ""));

    if ($From === "webmaster" || $Strprtl !== "") {
      $CanonicalUrl = $this->buildAbsoluteUrl("/");
      $Response = new RedirectResponse($CanonicalUrl, 301);
      $Response->headers->set('Link', '<' . $CanonicalUrl . '>; rel="canonical"');
      return $Response;
    }

    $GroupRep = $this->getNewGroupRepository();
    $GroupRep->setCurrentGroupId(\NewCMS\Libs\Config\NewCmsConfig::current()->get('StartGroup'));
    $GroupRep->setOrderBy();
    $GroupRep->setPresentFlagCondition();
    $GroupList = $GroupRep->getAll();

    $Title = ucfirst(\NewCMS\Libs\Config\NewCmsConfig::current()->get('CommonTitle'));
    $PageTitle = $Title . " - " . \NewCMS\Libs\Config\NewCmsConfig::current()->get('SiteName');
    $Description = \NewCMS\Libs\Config\NewCmsConfig::current()->get('CommonDescription');

    $this->setSEO($PageTitle, $Description, \NewCMS\Libs\Config\NewCmsConfig::current()->get('CommonKeyWords'));
    $this->view->setCanonical($this->buildAbsoluteUrl("/"));
    $this->setTwitterCard("summary_large_image", array(
      "title" => $PageTitle,
      "description" => $Description,
      "image" => $this->buildAbsoluteUrl("/images/canopy-new.webp"),
    ));
    $this->addWebSiteJsonLd(array(
      "url" => $this->buildAbsoluteUrl("/"),
      "name" => \NewCMS\Libs\Config\NewCmsConfig::current()->get('SiteName'),
      "potentialAction" => array(
        "@type" => "SearchAction",
        "target" => $this->buildAbsoluteUrl("/search?SearchText={search_term_string}"),
        "query-input" => "required name=search_term_string",
      ),
    ));
    $this->addJsonLd($this->getOrganizationJsonLd());

    $this->view->setVar("GroupList", $GroupList);
    $this->view->setVar("PageTitle",  $Title);

    return $this->view->render();
  }

  public function actionAbout()
  {
    $Title = "О компании " . \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyName');
    $Description = "Информация о компании " . \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyName') . ", поставке подвесных потолков и комплектующих.";

    $this->setSEO($Title, $Description, $Title);
    $this->view->setMeta("robots", "INDEX,FOLLOW");
    $this->view->setCanonical($this->buildAbsoluteUrl("/about"));
    $this->setTwitterCard("summary", array(
      "title" => $Title,
      "description" => $Description,
      "image" => $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
    ));
    $this->addAboutPageJsonLd(array(
      "name" => $Title,
      "url" => $this->buildAbsoluteUrl("/about"),
      "description" => $Description,
    ));
    $this->addJsonLd($this->getOrganizationJsonLd());

    $this->view->setVar("PageTitle", $Title);

    return $this->view->render();
  }

  public function actionContacts()
  {
    $Title = \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyName') . " - контакты";
    $Description = \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyName') . " - контакты, схема проезда, реквизиты";

    $this->setSEO($Title, $Description, \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyName') . ", контакты, реквизиты");
    $this->view->setMeta("robots", "INDEX,FOLLOW");
    $this->view->setCanonical($this->buildAbsoluteUrl("/contacts"));
    $this->setTwitterCard("summary", array(
      "title" => $Title,
      "description" => $Description,
      "image" => $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
    ));
    $this->addContactPageJsonLd(array(
      "name" => $Title,
      "url" => $this->buildAbsoluteUrl("/contacts"),
      "description" => $Description,
    ));
    $this->addJsonLd(array(
      '@context' => 'https://schema.org',
      '@type' => 'LocalBusiness',
      'name' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyName'),
      'url' => $this->buildAbsoluteUrl('/contacts'),
      'email' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('email'),
      'telephone' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('tel'),
      'address' => array(
        '@type' => 'PostalAddress',
        'streetAddress' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyAddress'),
        'addressLocality' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyCity'),
        'postalCode' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('PostalCode'),
        'addressCountry' => 'RU',
      ),
    ));

    $this->view->setVar("PageTitle", $Title);

    return $this->view->render();
  }

  public function actionAgreement()
  {
    $Title = "Пользовательское соглашение " . \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyName');
    $Description = \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyName') . " - пользовательское соглашение";

    $this->setSEO($Title, $Description, \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyName') . ", пользовательское соглашение");
    $this->view->setMeta("robots", "NOINDEX,NOFOLLOW");
    $this->view->setCanonical($this->buildAbsoluteUrl("/agreement"));
    $this->addWebPageJsonLd(array(
      "name" => $Title,
      "url" => $this->buildAbsoluteUrl("/agreement"),
      "description" => $Description,
    ));

    $this->view->setVar("PageTitle", $Title);

    return $this->view->render();
  }


  /**
   * группы товаров и список товаров , выбранных по фильтру
   * 
   * @return string - содержимое страницы
   * @throws TRMException
   */
  public function actionPrice()
  {
    $GroupRepository = $this->getNewGroupRepository(); // new NewGroupRepository();
    $GroupURL = '';
    $PresentGroupsCount = 0;

    $CountResult = $this->getDBObject()->query("SELECT COUNT(*) AS cnt FROM `group` WHERE `GroupPresent`=1");
    if ($CountResult) {
      $CountRow = $CountResult->fetch_array(MYSQLI_ASSOC);
      if ($CountRow && isset($CountRow['cnt'])) {
        $PresentGroupsCount = (int)$CountRow['cnt'];
      }
    }

    $GroupRepository->setFullParentInfoFlag(true);

    $param = $this->Request->attributes->get("param");
    $filters = $this->Request->query->get("filters");

    if (empty($param)) {
      $Group = $GroupRepository->getById(\NewCMS\Libs\Config\NewCmsConfig::current()->get('StartGroup'));
      if (!$Group && $PresentGroupsCount === 1) {
        $SingleGroupResult = $this->getDBObject()->query("SELECT `ID_group` FROM `group` WHERE `GroupPresent`=1 ORDER BY `ID_group` ASC LIMIT 1");
        if ($SingleGroupResult) {
          $SingleGroupRow = $SingleGroupResult->fetch_array(MYSQLI_ASSOC);
          if ($SingleGroupRow && isset($SingleGroupRow['ID_group'])) {
            $Group = $GroupRepository->getById((int)$SingleGroupRow['ID_group']);
          }
        }
      }
      //        throw new TRMException(__METHOD__ . " Не выбран раздел каталога!", 404);
    } else {
      if (strpos($param, "/") !== false) {
        $arr = explode("/", $param);
        $GroupURL = $arr[0];
      } else {
        $GroupURL = $param;
      }

      $TranslitFieldName = NewGroup::getTranslitFieldName(); //->getTranslitFieldName();

      $Group = $GroupRepository->getOneBy($TranslitFieldName[0], $TranslitFieldName[1], $GroupURL);
    }

    if (!$Group) {
      throw new TRMException(__METHOD__ . " Данные группы получить не удалось [{$GroupURL}]", 404);
    }

    $GroupId = intval($Group->getId());
    $Canonical = "/" . trim(\NewCMS\Libs\Config\NewCmsConfig::current()->get('pricePrefix'), "/\\");

    if ($GroupId !== \NewCMS\Libs\Config\NewCmsConfig::current()->get('StartGroup')) {
      $Canonical .= "/" . $Group->getTranslit();
    }

    $OriginalTitle = $Group->generateGroupFullTitle();

    $Title = $OriginalTitle . " - цены";
    $KeyWords = $OriginalTitle . " цены";
    if ($Group->getId() == \NewCMS\Libs\Config\NewCmsConfig::current()->get('GlobalStartGroup')) {
      $Description = \NewCMS\Libs\Config\NewCmsConfig::current()->get('CommonDescription') . ". " . $Group["group"]["GroupTitle"];
    } else if (!$Group["group"]["GroupPresent"]) {
      $Description = "";
    } else {
      $Description = "Предлагаем купить " . $OriginalTitle . ". Цены указаны с НДС. Есть доставка по Москве и области, отправляем в регионы РФ";
    }

    if (strlen(trim($Group->getData("group", "GroupImage") ?? "")) > 0) {
      $this->view->setVar("ImgSrc", $Group->getData("group", "GroupImage"));
    }
    if (strlen(trim($Group->getData("group", "GroupBigImage") ?? "")) > 0) {
      $this->view->setVar("BigImage", $Group->getData("group", "GroupBigImage"));
    }

    //--------------------------- FEATURES -------------------------------------------------------------
      if (empty($param) && $PresentGroupsCount === 1) {
        $GroupProductsCount = 0;
        $GroupProductsCountResult = $this->getDBObject()->query(
          "SELECT COUNT(*) AS cnt FROM `table1` WHERE `Group`=" . intval($GroupId) . " AND `present`=1"
        );
        if ($GroupProductsCountResult) {
          $GroupProductsCountRow = $GroupProductsCountResult->fetch_array(MYSQLI_ASSOC);
          if ($GroupProductsCountRow && isset($GroupProductsCountRow['cnt'])) {
            $GroupProductsCount = (int)$GroupProductsCountRow['cnt'];
          }
        }

        if ($GroupProductsCount === 0) {
          $Canonical = "/" . trim(\NewCMS\Libs\Config\NewCmsConfig::current()->get('pricePrefix'), "/\\");
          $Title = $OriginalTitle . " - цены";

          $this->setSEO(
            htmlspecialchars($Title),
            htmlspecialchars($Description),
            str_replace(array("\"", "\'", "<", ">", "(", ")", "{", "}", "[", "]", ".", ","), " ", $KeyWords)
          );
          $this->view->setCanonical($Canonical, true);

          $this->view->setVar("MyGoodsList", new NewLiteProductForCollection());
          $this->view->setVar("PageTitle", $Title);
          $this->view->setVar("OriginalTitle", $OriginalTitle);
          $this->view->setVar("CountOfGoods", 0);
          $this->view->setVar("StartGroup", $GroupId);
          $this->view->setVar("GroupPresent", $Group["group"]["GroupPresent"]);
          $this->view->setVar("GroupComment", $Group["group"]["GroupComment"]);
          $this->view->setVar("ShowDescriptionFlag", true);
          $this->view->setVar("catalogflag", true);

          $this->view->addCSS(TOPIC . "/css/forcatalogpage.css", true);
          $this->view->addCSS(TOPIC . "/css/selector.css", false);

          ob_start();
          $this->view->render();
          return new Response(ob_get_clean(), 200);
        }
      }

      $FeaturesSelector = new NewFeaturesSelector($this->getDBObject());
    $FeaturesSelector->setCurrentGroupId($GroupId, $Group["group"]["GroupTranslit"]);
    $FeaturesSelector->selectFeaturesFromURL($filters);

    //--------------------------- PRODUCTS -------------------------------------------------------------

    $ProductsListRepository = $this->getNewLiteProductForCollectionRepository(); // new NewLiteProductForCollectionRepository();
    $ProductsListRepository->setCurrentGroupId($Group->getId());
    $ProductsListRepository->setPresentFlagCondition();


    $MyGoodsList = new NewLiteProductForCollection();

    // если массив выбранных характеристик не пуст, 
    // значит будет сформирован список товаров согласно выбранным характеристикам
    if (!empty($FeaturesSelector->SelectedFeaturesList)) {
      $tmpTitle = $FeaturesSelector->generateTitleStrFromURL($param);
      if (strlen($tmpTitle)) {
        $Title = $OriginalTitle . " (" . $tmpTitle . ") - цены";
      }
      $ProductsListRepository->setFeaturesList($FeaturesSelector->SelectedFeaturesList);
    }

    $NumOfGoods = \NewCMS\Libs\Config\NewCmsConfig::current()->get('PaginationCount');

    // с какой позиции и сколько выбираем из БД
    $ProductsListRepository->setLimit($NumOfGoods, ($this->page - 1) * $NumOfGoods);

    //--------------------------- WHERE PARAMS -------------------------------------------------------------
    // в этой версии параметры сортировки не показываем...
    $this->view->setVar("OrderLink", false);

    $SelectedVendorId = $this->Request->query->getInt("VendorId", -1);
    if (-1 !== $SelectedVendorId) {
      $ProductsListRepository->addCondition("table1", "vendor", $SelectedVendorId);
      $this->view->setVar("VendorId", $SelectedVendorId);
    }
    if ($this->Request->query->get("NotEmptyFlag", null) !== null) {
      $this->view->setVar("NotEmptyFlag", true);
      $ProductsListRepository->addCondition("table1", "presentcount", "", "<>", "AND")->addCondition("table1", "presentcount", "NULL", "<>", "AND")->addCondition("table1", "presentcount", "0", "<>", "AND");
    }
    $this->view->setVar("PriceSort", $this->Request->query->get("PriceSort", 1));
    //    $ProductsListRepository->setOrderBy("price0", $this->Request->query->getBoolean("PriceSort", true) );

    $MaxPrice = floatval($this->Request->query->get("MaxPrice", 0));
    if ($MaxPrice !== 0.0) {
      $this->view->setVar("MaxPrice", $MaxPrice);
      // addCondition($objectname, $fieldname, $data, $operator = "=", $andor = "AND", $quote = TRMSqlDataSource::NEED_QUOTE, $alias = null, $dataquote = TRMSqlDataSource::NEED_QUOTE )
      $ProductsListRepository->addCondition(
        "table1",
        "price0",
        "CASE WHEN `valuta`=1 THEN " . $MaxPrice . "*100/(100+`pr3`)"
          . " ELSE CASE WHEN `valuta`=2 THEN " . TRMValuta::convert($MaxPrice, 1, 2) . "*100/(100+`pr3`)"
          . " ELSE CASE WHEN `valuta`=3 THEN " . TRMValuta::convert($MaxPrice, 1, 3) . "*100/(100+`pr3`)"
          . " END END END",
        "<",
        "AND",
        TRMSqlDataSource::NEED_QUOTE,
        null,
        TRMSqlDataSource::NOQUOTE
      );
    }

    // список товаров из группы и подгрупп,
    // если заданы, то с выбранными характеристиками,
    // за выбор отвечает $FeaturesSelector...
    // getProductsCount вернет количество записей, которые содержатся в БД по данному запросу
    $CountOfGoods = $ProductsListRepository->getProductsCount();

    if (!$CountOfGoods) {
      $this->view->setVar("MyGoodsList", $MyGoodsList);
      if ($PresentGroupsCount > 1 && !empty($filters)) {
        $Title = $OriginalTitle . " ( с выбранными характеристиками найти товары не удалось )";
      }
    } else {
      $MyGoodsList = $ProductsListRepository->getAll();
      $this->view->setVar("MyGoodsList", $MyGoodsList);
    }

    //--------------------------- PRESTIGE -------------------------------------------------------------
    // создаем вид для популярных товаров, если они есть и если не выбраны характеристики
    // if (strpos($param, "-eqv-") === false) {
    if (empty($filters)) {
      $PrestigeProductObject = $this->DIC->get(NewPrestigeProducts::class);
      $PrestigeProductObject->setGroupId($GroupId);
      if ($SelectedVendorId > -1) {
        $PrestigeProductObject->setVendorId($SelectedVendorId);
      }
      $PrestigeProducts = $PrestigeProductObject->getPrestigeProducts();

      if ($PrestigeProducts && $PrestigeProducts->count()) {
        $this->view->setVar("PrestigeProducts", $PrestigeProducts);
      }
    }

    //--------------------------- PAGINATION -------------------------------------------------------------
    // формируем блок ссылок постраничной навигации
    $MyPaginationClass = new NewPagination($CountOfGoods, $NumOfGoods);
    $MyPaginationClass->SetCurrentPageFromURI();
    $MyPaginationClass->GenerateLinksList();
    $this->view->setVar("PaginationLinks", $MyPaginationClass);

    if ($MyPaginationClass->CountOfPages > 1) {
      $this->addPaginationLinks(
        $MyPaginationClass->CurrentPage > 1 ? $this->buildAbsoluteUrl($MyPaginationClass->generateURLString($MyPaginationClass->CurrentPage - 1)) : null,
        $MyPaginationClass->CurrentPage < $MyPaginationClass->CountOfPages ? $this->buildAbsoluteUrl($MyPaginationClass->generateURLString($MyPaginationClass->CurrentPage + 1)) : null
      );
    }

    //--------------------------- CRUMBS -------------------------------------------------------------
    $MyCrumbs = new GroupCrumbs();

    GroupCrumbs::getParents($this->getDBObject(), $GroupId, $MyCrumbs);
    $this->view->setVar("MyCrumbs", $MyCrumbs);
    $this->addBreadcrumbJsonLd($MyCrumbs);

    //--------------------------- SUBGROUP LIST -------------------------------------------------------------
    $PageTitle = $Title;

    if ($this->page <= 1) {
      $GroupRepository->setCurrentGroupId($Group->getId());
      $GroupRepository->setPresentFlagCondition();
      $GroupRepository->setOrderField("GroupOrder");
      $GroupList = $GroupRepository->getAll();

      if ($GroupList && $GroupList->count()) {
        $this->view->setVar("GroupList", $GroupList);
      }

      $this->view->setVar("GroupComment", $Group["group"]["GroupComment"]);
    } else {
      $PageTitle .= ", страница {$this->page}";
    }

    //--------------------------- SET VARS -------------------------------------------------------------
    $this->view->setVar("GroupPresent", $Group["group"]["GroupPresent"]);

    //    $FeaturesSelector->generateFeaturesValsArray();
    $this->view->setVar("FeaturesSelector", $FeaturesSelector);

    $GroupImage = "";
    if (strlen(trim($Group->getData("group", "GroupImage") ?? "")) > 0) {
      $GroupImage = $Group->getData("group", "GroupImage");
    } elseif (strlen(trim($Group->getData("group", "GroupBigImage") ?? "")) > 0) {
      $GroupImage = $Group->getData("group", "GroupBigImage");
    }

    $PriceCanonical = $Canonical;
    if (!empty($filters) && !empty($FeaturesSelector->SelectedFeaturesList) && $CountOfGoods > 0) {
      $PriceCanonical .= '?filters=' . rawurlencode($filters);
    }

    $this->setSEO(
      htmlspecialchars($Title),
      htmlspecialchars($Description),
      str_replace(array("\"", "\'", "<", ">", "(", ")", "{", "}", "[", "]", ".", ","), " ", $KeyWords)
    );
    $this->view->setCanonical($PriceCanonical, true);
    $this->setTwitterCard("summary_large_image", array(
      "title" => $Title,
      "description" => $Description,
      "image" => $GroupImage ? $this->buildAbsoluteUrl($GroupImage) : $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
    ));
    $this->addCollectionPageJsonLd(array(
      "name" => $Title,
      "url" => $this->buildAbsoluteUrl($Canonical),
      "description" => $Description,
      "image" => $GroupImage ? $this->buildAbsoluteUrl($GroupImage) : null,
    ));

    $this->view->setVar("PageTitle", $PageTitle);
    //    $this->view->setVar("Description", $Description);
    $this->view->setVar("OriginalTitle", $OriginalTitle);
    $this->view->setVar("CountOfGoods", $CountOfGoods);
    $this->view->setVar("StartGroup", $GroupId);
    $this->view->setVar("ShowDescriptionFlag", true);

    $this->view->setVar("catalogflag", true);

    $this->view->addCSS(TOPIC . "/css/forcatalogpage.css", true);
    $this->view->addCSS(TOPIC . "/css/selector.css", false);

    //--------------------------- GROUP++ -------------------------------------------------------------
    $Group->setData("group", "GroupVisits", $Group["group"]["GroupVisits"] + 1);
    $GroupRepository->update($Group);
    $GroupRepository->doUpdate();

    if (!$Group["group"]["GroupPresent"]) {
      $Code = 404;
    } else {
      $Code = 200;
    }

    ob_start();
    $this->view->render();

    return new Response(ob_get_clean(), $Code); //(string)$this->view, $Code);
  }

  public function getMyComplect(NewComplexProductRepository $ComplexRepository, $param): NewComplexProduct
  {
    return $ComplexRepository->getOneBy("table1", "PriceTranslit", $param);
  }

  /**
   * action что бы работать с отображением страницы товаров
   * 
   * @return string - содержимое страницы
   * @throws Exception - если товар не найден, выбрасывается исключение
   */
  public function actionCatalog()
  {
    $param = $this->Request->attributes->get("param");
    //если не передан адрес товара, то такой страницы нет - 404 ошибка
    if (empty($param)) {
      throw new \Exception(__METHOD__ . " Не передан адрес товара!", 404);
    }

    $ComplexRepository = $this->getComplexRepository(); // new NewComplexProductRepository();

    try {
      // $MyComplect1 = $ComplexRepository->getOneBy("table1", "PriceTranslit", $param);
      $MyComplect1 = $this->getMyComplect($ComplexRepository, $param);
    } catch (TRMRepositoryNoDataObjectException $e) {
      throw new NewProductsWrongIdExceptions("Документ с адресом {$param} не найден!", 404, $e);
    }

    $ParentFeaturesList = null;
    $ParentProduct = null;

    $LiteProduct = $MyComplect1->getLiteProduct();

    if ($LiteProduct["table1"]["ParentId"]) {
      $ParenProductId = $LiteProduct["table1"]["ParentId"];
      $ParentFeaturesList = $this->_RM->getRepository(NewProductFeature::class)
        //                (new NewProductFeatureRepository())
        ->getBy("goodsfeatures", "ID_Price", $ParenProductId);

      $ParentProduct = $this->getLiteProductRepository()
        //(new NewLiteProductRepository)
        ->getById($ParenProductId);

      $ProductFeaturesList = $MyComplect1["ProductFeaturesCollection"];
      // удаляем из родительской коллекции характеристики, 
      // которые есть в самом товаре-модели
      foreach ($ProductFeaturesList as $ProductFeature) {
        foreach ($ParentFeaturesList as $Index => $ParentProductFeature) {
          if (
            $ParentProductFeature->getData("goodsfeatures", "ID_Feature")
            == $ProductFeature->getData("goodsfeatures", "ID_Feature")
          ) {
            $ParentFeaturesList->removeDataObject($Index);
          }
        }
      }

      $this->view->setVar("ParentProduct", $ParentProduct);
      $this->view->setVar("ParentFeaturesList", $ParentFeaturesList);
    }

    $Canonical = "/"
      . trim(\NewCMS\Libs\Config\NewCmsConfig::current()->get('catalogPrefix'), "/\\")
      . "/" . $LiteProduct["table1"]["PriceTranslit"]; // $_SERVER["REQUEST_URI"];

    //--------------------------- CRUMBS -------------------------------------------------------------
    $MyCrumbs = new GroupCrumbs();

    GroupCrumbs::getParents($this->getDBObject(), $LiteProduct["table1"]["Group"], $MyCrumbs);
    $MyCrumbs->addCrumb($LiteProduct["table1"]["Name"], $Canonical);
    $this->view->setVar("MyCrumbs", $MyCrumbs);
    $this->addBreadcrumbJsonLd($MyCrumbs);

    //--------------------------- META -------------------------------------------------------------

    $Title = $LiteProduct["table1"]["Name"] . ", "
      . $MyComplect1->getMainDataObject()->getVendorObject()["vendors"]["VendorName"]
      . ", цена";
    $KeyWords = $Title;

    //описание для страницы мета тег!!!
    $Price = null;
    if (\NewCMS\Libs\Config\NewCmsConfig::current()->get('PriceColumnCount') == 1) {
      $Price = $LiteProduct->getData("table1", "Price3");
    }
    if (\NewCMS\Libs\Config\NewCmsConfig::current()->get('PriceColumnCount') == 2) {
      $Price = $LiteProduct->getData("table1", "Price2");
    }
    if (\NewCMS\Libs\Config\NewCmsConfig::current()->get('PriceColumnCount') == 3) {
      $Price = $LiteProduct->getData("table1", "Price1");
    }

    $Description = ($Price ? "Цена на " : "Уточняйте цену на ")
      . $LiteProduct["table1"]["Name"]
      . " производства "
      . $MyComplect1->getMainDataObject()->getVendorObject()["vendors"]["VendorName"] . " "
      . ($Price
        ? "от " . (($Price) . " руб/" . $LiteProduct->getData("unit", "UnitShort"))
        : "") . " "
      . ". Есть скидки. Возможна доставка по Москве и МО, отправляем в регионы РФ.";


    // ************************ LINK ************************************************
    $IdsArr = NewHelper::createLinkRows(
      $this->getDBObject(),
      10,
      $LiteProduct->getData("table1", "ID_price"),
      $LiteProduct->getData("table1", "Group")
    );
    if (!empty($IdsArr)) {
      $IdStr = implode(",", $IdsArr);
      $LinkRep = $this->_RM->getRepository(NewLiteProductForCollection::class); // new NewLiteProductForCollectionRepository();

      $LinkRep->setPresentFlagCondition();
      $LinkRep->addCondition("table1", "ID_price", $IdStr, "IN");

      $LinkProductsList = $LinkRep->getAll();
      if ($LinkProductsList) {
        $this->view->setVar("LinkProductsList", $LinkProductsList);
      }
    }

    //--------------------------- setVar -------------------------------------------------------------

    $this->view->addCSS(TOPIC . "/css/forcatalogpage.css", true);
    $this->view->addCSS(TOPIC . "/css/selector.css", false);
    $ProductTitle = htmlspecialchars($LiteProduct["table1"]["Name"]);
    $ProductImage = "";
    if (strlen(trim($LiteProduct["table1"]["Image"] ?? "")) > 0) {
      $ProductImage = "/" . trim(\NewCMS\Libs\Config\NewCmsConfig::current()->get('ImageCatalog'), "/") . "/" . $LiteProduct["table1"]["Image"] . ".jpg";
    }

    $this->setSEO($ProductTitle, htmlspecialchars($Description), str_replace(array("\"", "\'", "<", ">", "(", ")", "{", "}", "[", "]", ".", ","), " ", $KeyWords));
    $this->view->setCanonical($Canonical, true);
    $this->view->setVar("Description", $Description);
    $this->setProductSocialMeta(
      $ProductTitle,
      $Description,
      $ProductImage ? $this->buildAbsoluteUrl($ProductImage) : $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
      $LiteProduct["table1"]["Name"]
    );
    $this->addProductJsonLd(array(
      "name" => $LiteProduct["table1"]["Name"],
      "description" => $Description,
      "image" => $ProductImage ? $this->buildAbsoluteUrl($ProductImage) : null,
      "url" => $this->buildAbsoluteUrl($Canonical),
      "sku" => $LiteProduct["table1"]["articul"] ?: null,
      "brandName" => $MyComplect1->getMainDataObject()->getVendorObject()["vendors"]["VendorName"],
      "offer" => array(
        "url" => $this->buildAbsoluteUrl($Canonical),
        "priceCurrency" => "RUB",
        "price" => $Price ?: null,
        "availability" => $LiteProduct["table1"]["present"] ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
      ),
    ));

    $this->view->setVar("noShowImage", true);

    //    $this->view->setVar("catalogflag", true);
    $this->view->setVar("StartGroup", $LiteProduct["table1"]["Group"]);
    $this->view->setVar("StartGroupTranslit", $MyComplect1->getMainDataObject()->getGroupObject()["group"]["GroupTranslit"]);

    $this->view->setVar("ProductData", $LiteProduct["table1"]);

    $this->view->setVar("VendorData", $MyComplect1->getMainDataObject()->getVendorObject()["vendors"]);
    $this->view->setVar("UnitData", $LiteProduct["unit"]);
    $this->view->setVar("GoodsDescriptionData", $LiteProduct["goodsdescription"]);
    $this->view->setVar("ComplectData", $MyComplect1->getChildCollection("ComplectCollection"));
    $this->view->setVar("GoodsFeatures", $MyComplect1->getChildCollection("ProductFeaturesCollection"));
    $this->view->setVar("Images", $MyComplect1->getChildCollection("ImagesCollection"));

    $this->view->setVar("OgType", "product");

    //$this->view->setVar("LinkContent", true);

    //     запишем в куки, что был просмотрен этот товар
    //     для iOS это не срабортает, поэтому Cookie формируются на клиенте!!!
    //(new NewLastProducts( $this->_RM->getRepository(NewLiteProductForCollection::class) ))
    $this->DIC->get(NewLastProducts::class)
      ->setLastProducts($MyComplect1->getId());

    // увеличиваем кол-во просмотров товара на 1
    $LiteProduct->setData("table1", "Visits", $MyComplect1->getData("table1", "Visits") + 1);

    $LiteProductRep = $this->_RM->getRepositoryFor($LiteProduct);

    $LiteProductRep->update($LiteProduct);
    $LiteProductRep->doUpdate();

    if (!$LiteProduct["table1"]["present"]) {
      $Code = 404;
    } else {
      $Code = 200;
    }

    ob_start();
    $this->view->render();

    return new Response(ob_get_clean(), $Code); //(string)$this->view, $Code);
  }

  /**
   * редиректы со старых адресов товаров,
   * например, descr.php?ID_Price=13354
   * 
   * @return RedirectResponse
   * @throws NewProductsWrongIdExceptions
   */
  public function actionDescrReDirect()
  {
    $param = $this->Request->query->getInt("ID_Price");

    $ProductRep = $this->getLiteProductRepository(); // new NewLiteProductRepository();
    $MyComplect1 = $ProductRep->getById($param);

    if (!$MyComplect1) {
      throw new NewProductsWrongIdExceptions("Не удалось получить данные товара [" . $param . "] !", 404);
    }
    return new RedirectResponse(\NewCMS\Libs\Config\NewCmsConfig::current()->get('catalogPrefix') . "/" . $MyComplect1["table1"]["PriceTranslit"], 301);
  }

  /**
   * редиректы со старых адресов групп,
   * например, catalog.php?group=429
   * 
   * @return RedirectResponse
   * @throws NewGroupWrongNumberException
   */
  public function actionCatalogReDirect()
  {
    $param = $this->Request->query->getInt("group");

    $GroupRep = $this->_RM->getRepository(NewGroup::class); // new NewGroupRepository();

    $Group = $GroupRep->getById($param);

    if (!$Group) {
      throw new NewGroupWrongNumberException("Не удалось получить данные для группы [" . $param . "] !", 404);
    }
    return new RedirectResponse(\NewCMS\Libs\Config\NewCmsConfig::current()->get('pricePrefix') . "/" . $Group["group"]["GroupTranslit"], 301);
  }
} // MainController
