<?php

namespace NewCMS\Controllers;

use GlobalConfig;
use NewCMS\Domain\Exceptions\NewArticlesEmptyCollectionExceptions;
use NewCMS\Domain\Exceptions\NewArticlesExceptions;
use NewCMS\Domain\Exceptions\NewArticlesWrongTypeExceptions;
use NewCMS\Domain\NewArticle;
use NewCMS\Domain\NewArticlesType;
use NewCMS\Domain\NewComplexArticle;
use NewCMS\Domain\NewVendor;
use NewCMS\Repositories\NewArticleRepository;
use NewCMS\Repositories\NewArticlesTypeRepository;
use NewCMS\Repositories\NewComplexArticleRepository;
use NewCMS\Repositories\NewVendorRepository;
use NewCMS\Widgets\ArticleCrumbs;
use NewCMS\Widgets\NewPagination;
use Symfony\Component\HttpFoundation\RedirectResponse;
use TRMEngine\Exceptions\TRMObjectCreateException;
use TRMEngine\Helpers\TRMLib;
use TRMEngine\Repository\Exceptions\TRMRepositoryNoDataObjectException;

class ArticlesController extends BaseController
{
  /**
   * протокол по умолчанию, если не гайдется в глобальных настроек $_SERVER
   */
  const DEFAULT_PROTOCOL = "http";

  protected $CurrentURL = "";
  protected $LocExceptions = array();
  protected $CurrentURLPrefix = "";

  public function actionRal()
  {
    $Title = "Таблица цветов RAL - " . GlobalConfig::$ConfigArray["SiteName"];
    $Description = "Шкала цветов RAL, применяется при покраске металлических подвесных потолков";

    $this->setSEO($Title, $Description, "цвет RAL покраска потолка");
    $this->view->setCanonical($this->buildAbsoluteUrl("/" . strtolower($this->CurrentActionName)));
    $this->setTwitterCard("summary", array(
      "title" => $Title,
      "description" => $Description,
      "image" => $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
    ));
    $this->addWebPageJsonLd(array(
      "name" => $Title,
      "url" => $this->buildAbsoluteUrl("/" . strtolower($this->CurrentActionName)),
      "description" => $Description,
    ));
    $this->view->setVar("PageTitle", "Таблица цветов RAL");

    return $this->view->render();
  }

  protected function getNewVendorRepository(): NewVendorRepository
  {
    return $this->_RM->getRepository(NewVendor::class);
  }

  protected function getNewArticlesTypeRepository(): NewArticlesTypeRepository
  {
    return $this->_RM->getRepository(NewArticlesType::class);
  }

  protected function getNewArticleRepository(): NewArticleRepository
  {
    return $this->_RM->getRepository(NewArticle::class);
  }

  protected function getNewComplexArticleRepository(): NewComplexArticleRepository
  {
    return $this->_RM->getRepository(NewComplexArticle::class);
  }

  public function actionTrademark()
  {
    //    $Rep = new NewVendorRepository();
    $Rep = $this->getNewVendorRepository();

    $CountOfArticles = $Rep->getCountOfVendors();
    $NumOfArticles = GlobalConfig::$ConfigArray["NumArticlesPerPage"];

    $MyPaginationClass = new NewPagination($CountOfArticles, $NumOfArticles);
    $MyPaginationClass->SetCurrentPageFromURI();
    $MyPaginationClass->GenerateLinksList();
    $this->view->setVar("PaginationLinks", $MyPaginationClass);

    $Rep->setLimit($NumOfArticles, intval(($MyPaginationClass->CurrentPage - 1) * $NumOfArticles));

    $Rep->setOrderBy("VendorName");
    $VendorsList = $Rep->getAll();
    $this->view->setVar("VendorsList", $VendorsList);

    if ($MyPaginationClass->CountOfPages > 1) {
      $this->addPaginationLinks(
        $MyPaginationClass->CurrentPage > 1 ? $this->buildAbsoluteUrl($MyPaginationClass->generateURLString($MyPaginationClass->CurrentPage - 1)) : null,
        $MyPaginationClass->CurrentPage < $MyPaginationClass->CountOfPages ? $this->buildAbsoluteUrl($MyPaginationClass->generateURLString($MyPaginationClass->CurrentPage + 1)) : null
      );
    }

    $Title = "Торговые марки и производители - " . GlobalConfig::$ConfigArray["SiteName"];
    $Description = "Торговые марки и производители подвесных потолков, светильников и комплектующих";
    $Canonical = "/" . strtolower($this->CurrentActionName);

    $this->setSEO($Title, $Description, "торговые марки производители подесные потолки");
    $this->view->setCanonical($this->buildAbsoluteUrl($Canonical));
    $this->setTwitterCard("summary", array(
      "title" => $Title,
      "description" => $Description,
      "image" => $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
    ));
    $this->addCollectionPageJsonLd(array(
      "name" => $Title,
      "url" => $this->buildAbsoluteUrl($Canonical),
      "description" => $Description,
    ));
    $this->view->setVar("PageTitle", "Торговые марки и производители");

    return $this->view->render();
  }

  private function generateCurrentURL($DocType = null)
  {
    $SiteMapConfig = include CONFIG . "/sitemapconfig.php";
    if (!empty($SiteMapConfig["Exceptions"])) {
      $this->LocExceptions = $SiteMapConfig["Exceptions"];
    }
    if (!$DocType) {
      return;
    }
    // если текущий адрес документов есть в массиве исключений,
    // т.е. добавляется к адресу сайта напрямую без префикса, 
    // то формируем соответсвующий префикс для текущего документа
    if (
      !empty($this->LocExceptions)
      && in_array($DocType, $this->LocExceptions)
    ) {
      $this->CurrentURL = $DocType;
    }
    // иначе текущий префикс включает общий префикс для документов
    else {
      $this->CurrentURL = trim(GlobalConfig::$ConfigArray["articlesListPrefix"], "\\/") . "/" . $DocType;
    }
  }

  public function actionBase()
  {
    $DocName = $this->Request->attributes->get("docname", null);
    $DocType = $this->Request->attributes->get("doctype", null);

    $this->generateCurrentURL($DocType);
    $this->view->setVar("articleflag", true);

    if (!empty($DocName)) {
      return $this->actionViewArticle($DocName);
    }

    if (!empty($DocType)) {
      return $this->actionArticlesList($DocType);
    }

    $Rep = $this->getNewArticlesTypeRepository();
    $Rep->setPresentFlag();

    $ArticlesTypeList = $Rep->getAll();

    $this->view->setViewName("articlestypelist");

    $Title = "Информационные разделы";
    $Description = "Информационные разделы сайта " . GlobalConfig::$ConfigArray["SiteName"];
    $Canonical = "/" . GlobalConfig::$ConfigArray["articlesListPrefix"];

    $this->setSEO($Title, $Description, $Title);
    $this->view->setCanonical($this->buildAbsoluteUrl($Canonical));
    $this->setTwitterCard("summary", array(
      "title" => $Title,
      "description" => $Description,
      "image" => $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
    ));
    $this->addCollectionPageJsonLd(array(
      "name" => $Title,
      "url" => $this->buildAbsoluteUrl($Canonical),
      "description" => $Description,
    ));

    $this->view->setVar("PageTitle", "Информационные разделы");

    $this->view->setVar("LocExceptions", $this->LocExceptions);
    $this->view->setVar("ArticlesTypeList", $ArticlesTypeList);

    return $this->view->render();
  }

  /**
   * страница со списком документов определенноготипа
   * 
   * @param string $DocType - URL для типа документов,
   * если в базе нет такого адреса, выбрасывается исключение
   * 
   * @return string - HTML-представление страницы
   * 
   * @throws NewArticlesEmptyCollectionExceptions
   */
  protected function actionArticlesList($DocType)
  {
    $ArticlesTypeRep = $this->_RM->getRepository(NewArticlesType::class); // new NewArticlesTypeRepository();
    $Type = $ArticlesTypeRep->getOneBy("articlestype", "ArticlesURL", $DocType);
    if (!$Type) {
      throw new NewArticlesWrongTypeExceptions("Не удалось получить описание типа документов для {$DocType}!", 404);
    }
    $ArticleType =  $Type["articlestype"]["ID_articlestype"];
    $ArticleTypeName = $Type["articlestype"]["ArticlesTypeName"];

    $this->view->setViewName("base");

    $ArticlesListRepository = $this->getNewArticleRepository();

    $CountOfArticles = $ArticlesListRepository->getCountOfArticlesForUri($DocType);
    $NumOfArticles = GlobalConfig::$ConfigArray["NumArticlesPerPage"];

    $MyPaginationClass = new NewPagination($CountOfArticles, $NumOfArticles);
    $MyPaginationClass->SetCurrentPageFromURI();
    $MyPaginationClass->GenerateLinksList();
    $this->view->setVar("PaginationLinks", $MyPaginationClass);

    if ($MyPaginationClass->CountOfPages > 1) {
      $this->addPaginationLinks(
        $MyPaginationClass->CurrentPage > 1 ? $this->buildAbsoluteUrl($MyPaginationClass->generateURLString($MyPaginationClass->CurrentPage - 1)) : null,
        $MyPaginationClass->CurrentPage < $MyPaginationClass->CountOfPages ? $this->buildAbsoluteUrl($MyPaginationClass->generateURLString($MyPaginationClass->CurrentPage + 1)) : null
      );
    }

    $ArticlesListRepository->setLimit($NumOfArticles, intval(($MyPaginationClass->CurrentPage - 1) * $NumOfArticles));
    $ArticlesListRepository->setOrderField("ArticleDate", false);

    $MyArticlesList = $ArticlesListRepository->getBy("articlestype", "ArticlesURL", $DocType);

    if (!$MyArticlesList || !$MyArticlesList->count()) {
      $Title = $ArticleTypeName . " - список статей пуст!";
      $Description = $Title;
    } else {
      //        $ArticleType = $MyArticlesList[0]["articlestype"]["ID_articlestype"]; // ->getFirstArticleType();
      //        $ArticleTypeName = $MyArticlesList[0]["articlestype"]["ArticlesTypeName"]; // ->getFirstArticleTypeName();
      $Title = GlobalConfig::$ConfigArray["SiteName"] . " - {$ArticleTypeName}, страница " . $this->page;
      $Description = GlobalConfig::$ConfigArray["SiteName"] . " - "
        . $MyArticlesList[0]["articlestype"]["Comment"];
    }

    $CanonicalLink = "/"
      . $this->CurrentURL;
    //--------------------------- CRUMBS -------------------------------------------------------------
    $MyCrumbs = new ArticleCrumbs();

    //    \NewCMS\Widgets\ArticleCrumbs::getParents($ArticleType, $MyCrumbs);
    $MyCrumbs->addCrumb($ArticleTypeName, $CanonicalLink);
    $this->view->setVar("MyCrumbs", $MyCrumbs);
    $this->addBreadcrumbJsonLd($MyCrumbs);

    //--------------------------- META -------------------------------------------------------------
    $KeyWords = $Title;
    $PageTitle = $Title;

    //--------------------------- setVar -------------------------------------------------------------
    $this->view->setVar("ArticlesListData", $MyArticlesList);
    $this->view->setVar("ParamUrl", $this->CurrentURL);
    $this->setSEO($Title, $Description, $KeyWords);
    $this->view->setCanonical($this->buildAbsoluteUrl($CanonicalLink));
    $this->setTwitterCard("summary", array(
      "title" => $Title,
      "description" => $Description,
      "image" => $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
    ));
    $this->addCollectionPageJsonLd(array(
      "name" => $Title,
      "url" => $this->buildAbsoluteUrl($CanonicalLink),
      "description" => $Description,
    ));

    $this->view->setVar("PageTitle", $PageTitle);
    $this->view->setVar("ArticleType", $ArticleType);
    $this->view->setVar("ArticleTypeName", $ArticleTypeName);
    $this->view->setVar("CountOfArticles", $CountOfArticles);

    return $this->view->render();
  }

  protected function getMyArticle(NewComplexArticleRepository $ArticleRepository, $param): NewComplexArticle
  {
    return $ArticleRepository->getOneBy("articles", "ArticleURL", $param);
  }

  /**
   * страница с определенным документом
   * 
   * @param string $param - URL документа,
   * если в базе нет документа с таким адресом, выбрасывается исключение
   * 
   * @return string - HTML-представление страницы
   * 
   * @throws NewArticlesExceptions
   */
  protected function actionViewArticle($param)
  {
    $this->view->setViewName("viewarticle");

    //    $MyArticle = new NewComplexArticle();
    $ArticleRepository = $this->getNewComplexArticleRepository();

    try {
      $MyArticle = $this->getMyArticle($ArticleRepository, $param); // $ArticleRepository->getOneBy("articles", "ArticleURL", $param);
    } catch (TRMRepositoryNoDataObjectException $e) {
      throw new NewArticlesExceptions("Документ с адресом {$param} не найден!", 404, $e);
    }

    $ArticleTypeName = $MyArticle->getMainDataObject()->getArticleTypeName();
    $CanonicalLink = "/"
      . $this->CurrentURL
      . "/"
      . $MyArticle->getMainDataObject()->getData("articles", "ArticleURL");
    $ArticleType = $MyArticle->getMainDataObject()->getData("articlestype", "ID_articlestype");

    //--------------------------- CRUMBS -------------------------------------------------------------
    $MyCrumbs = new ArticleCrumbs();

    //    \NewCMS\Widgets\ArticleCrumbs::getParents($ArticleType, $MyCrumbs);
    $MyCrumbs->addCrumb($MyArticle->getMainDataObject()->getData("articles", "Title"), $CanonicalLink);
    $MyCrumbs->addCrumb($ArticleTypeName, "/" . $this->CurrentURL);
    $this->view->setVar("MyCrumbs", $MyCrumbs);
    $this->addBreadcrumbJsonLd($MyCrumbs);

    //--------------------------- setVar -------------------------------------------------------------

    $this->view->setVar("GroupList", $MyArticle->getChildCollection("ArticleGroupsCollection"));
    $this->view->setVar("MyArticle", $MyArticle->getMainDataObject());

    $ArticleTitle = $MyArticle->getMainDataObject()->getData("articles", "Title") . " [{$ArticleTypeName}]";
    $ArticlePreview = $MyArticle->getMainDataObject()->getData("articles", "Preview");
    $ArticleImage = $MyArticle->getMainDataObject()->getData("articles", "PreviewImage");
    $ArticleDate = $MyArticle->getMainDataObject()->getData("articles", "ArticleDate");
    $ArticleAuthor = $MyArticle->getMainDataObject()->getData("articles", "ArticleAuthor");

    $this->setSEO($ArticleTitle, $ArticlePreview, $MyArticle->getMainDataObject()->getData("articles", "keywords"));
    $this->view->setCanonical($this->buildAbsoluteUrl($CanonicalLink));
    $this->setArticleSocialMeta(
      $ArticleTitle,
      $ArticlePreview,
      $ArticleImage ? $this->buildAbsoluteUrl($ArticleImage) : $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
      $ArticleTitle,
      $ArticleDate ?: null,
      $ArticleDate ?: null,
      $ArticleAuthor ?: null
    );
    $this->addArticleJsonLd(array(
      "headline" => $ArticleTitle,
      "description" => $ArticlePreview,
      "image" => $ArticleImage ? $this->buildAbsoluteUrl($ArticleImage) : null,
      "datePublished" => $ArticleDate ?: null,
      "dateModified" => $ArticleDate ?: null,
      "authorName" => $ArticleAuthor ?: null,
      "publisherName" => GlobalConfig::$ConfigArray["CompanyName"],
      "publisherLogoUrl" => $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
      "url" => $this->buildAbsoluteUrl($CanonicalLink),
    ));


    $this->view->setVar("PageTitle", null);
    $this->view->setVar("ImgSrc", $MyArticle->getMainDataObject()->getData("articles", "PreviewImage"));

    $this->view->setVar("ArticleTypeName", $ArticleTypeName);
    $this->view->setVar("ArticleType", $ArticleType);
    $this->view->setVar("ArticlesURL", $MyArticle->getMainDataObject()->getData("articlestype", "ArticlesURL"));
    $this->view->setVar(
      "CountOfArticles",
      $this->getNewArticleRepository()
        ->getCountOfArticlesOfCurrentType(
          $MyArticle->getMainDataObject()->getData("articles", "Reserv")
        )
    );

    $this->view->setVar("noShowImage", true);

    $this->view->setVar("OgType", "article");

    return $this->view->render();
  }

  // пока не удаляю, но этот метод уже давно не работал,
  // так как в екм использовался устаревший класс Article и его метод getFromDB
  // так же отключаю роут, который вызывал этот экшн
  // /**
  //  * редиректы со старых адресов просмотра страниц
  //  */
  // public function actionViewReDirect()
  // {
  //   $param = $this->Request->query->getInt("id");
  //   // Article - устаревший класс, его уже нигде нет в проекте
  //   $MyArticle = new Article;

  //   // getFromDB - устаревший метод, его уже нигде нет в проекте
  //   if (false === $MyArticle->getFromDB(array("ID_article" => array("value" => $param)))) {
  //     throw new NewArticlesExceptions("Документ № {$param} не найден!", 404);
  //   }

  //   // getArticlesTypeName - устаревший метод, его уже нигде нет в проекте
  //   $ArticleTypeName = $MyArticle->getArticlesTypeName();

  //   $Response = new RedirectResponse(GlobalConfig::$ConfigArray["articlesListPrefix"] . "/{$ArticleTypeName}/" . $MyArticle->ArticleURL, 301);

  //   return $Response;
  // }


  public function actionArticlesTurboRSS()
  {
    header("content-type: application/xml;");
    echo $this->generateArticlesTurboRss();
    exit;
  }


  public function actionPotolkiTurboRSS()
  {
    header("content-type: application/xml;");
    echo $this->generateArticlesTurboRss(8);
    exit;
  }

//Turbo RSS-канал статей и прочих документов
  /**
   * 
   * @param int $id - ID-типа документов
   * 
   * @return boolean
   */
  private function generateArticlesTurboRss($id = 3)
  {
    $CommonURL = trim(filter_var($_SERVER["SERVER_NAME"], FILTER_SANITIZE_URL), "/\\");
    if (empty($CommonURL)) {
      throw new TRMObjectCreateException("Не задан CommonURL, необходимый для создания объекта NewSiteMap!");
    }

    if (key_exists("HTTP_X_REQUEST_SCHEME", $_SERVER)) {
      // $Protocol = filter_var($_SERVER["HTTP_X_REQUEST_SCHEME"], FILTER_SANITIZE_STRING); // $_SERVER["HTTP_X_REQUEST_SCHEME"];
      $Protocol = TRMLib::sanitize_string($_SERVER["HTTP_X_REQUEST_SCHEME"]);
    } else if (key_exists("REQUEST_SCHEME", $_SERVER)) {
      // $Protocol = filter_var($_SERVER["REQUEST_SCHEME"], FILTER_SANITIZE_STRING); // $_SERVER["REQUEST_SCHEME"];
      $Protocol = TRMLib::sanitize_string($_SERVER["REQUEST_SCHEME"]);
    } else {
      $Protocol = self::DEFAULT_PROTOCOL;
    }
    $Protocol .= "://";

    $query0 = "SELECT * FROM `articlestype` WHERE `articlestype`.`ID_articlestype`={$id}";
    $result0 = $this->getDBObject()->query($query0);

    if (!$result0 || $result0->num_rows <= 0) {
      echo "<pre>[{$query0}]</pre>";
      echo "Не удалось загрузить данные из БД для Articles Turbo RSS";
      return false;
    }
    $row0 = $result0->fetch_array(MYSQLI_ASSOC);

    $SiteMapConfig = include CONFIG . "/sitemapconfig.php";

    if (
      !empty($SiteMapConfig["Exceptions"])
      && in_array($row0['ArticlesURL'], $SiteMapConfig["Exceptions"])
    ) {
      $CurrentURLPrefix = "/{$row0['ArticlesURL']}";
    } else {
      $CurrentURLPrefix = "/"
        . trim(GlobalConfig::$ConfigArray["articlesListPrefix"], "/\\")
        . "/{$row0['ArticlesURL']}";
    }

    ob_start();

    echo "<?xml version='1.0' encoding='UTF-8'?>";
    echo "<rss xmlns:yandex='http://news.yandex.ru' xmlns:media='http://search.yahoo.com/mrss/' xmlns:turbo='http://turbo.yandex.ru' version='2.0'>";
    echo "<channel>";
    echo "<title>Подвесной.РУ - {$row0['ArticlesTypeName']}</title>"
      . "<link>https://www.podvesnoi.ru{$CurrentURLPrefix}</link>"
      . "<description>{$row0['Comment']}</description>"
      . "<language>ru</language>";

    $query = "SELECT * FROM `articles` WHERE `articles`.`onlyowner`=1 AND `articles`.`Reserv`={$id} ORDER BY `articles`.`ArticleDate` DESC ";
    $result = $this->getDBObject()->query($query);

    if (!$result || $result->num_rows <= 0) {
      echo "<pre>[{$query}]</pre>";
      echo "Не удалось загрузить данные из БД для Articles Turbo RSS";
      return false;
    }
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
      //            $row = TRMLib::conv($row, "windows-1251", "utf-8");
      echo "<item turbo='true'>"
        . "<link>"
        . $Protocol . $CommonURL . $CurrentURLPrefix . "/" . $row['ArticleURL']
        . "</link>"
        . "<pubDate>{$row['ArticleDate']}</pubDate>"
        . "<turbo:content>"
        . "<![CDATA[";
      echo "<header><h1>{$row['Title']}</h1>";

      if (!empty($row["PreviewImage"])) {
        echo "<figure><img src='/" . ltrim($row["PreviewImage"], "\//") . "' />";

        if (!empty($row["Preview"])) {
          echo "<figcaption>{$row["Preview"]}</figcaption>";
        }
        echo "</figure>";
      }
      echo "</header>";


      echo $row['Article'];
      echo "]]></turbo:content></item>";
    }

    echo "</channel>";
    echo "</rss>";

    return ob_get_clean();
    //    $Body = ob_get_clean();
    //
    //    return TRMLib::conv($Body, "windows-1251", "utf-8");
  }
} // ArticlesController
