<?php

namespace NewCMS\Controllers;

use NewCMS\Domain\NewSearchQuery;
use NewCMS\Libs\NewSearchObject;
use NewCMS\Repositories\NewSearchQueryRepository;
use TRMEngine\Exceptions\TRMObjectCreateException;

/**
 *  контроллер для поиска товаров
 */
class SearchController extends BaseController
{

protected function getSearchQueryRepository(): NewSearchQueryRepository
{
    return $this->_RM->getRepository(NewSearchQuery::class);
}

/**
 * поиск товаров в каталоге
 * 
 * @return string - содержимое страницы
 */
public function actionIndex()
{
    $this->view->addCss( TOPIC . "/css/search.css" , true);

    $Quest = $this->Request->query->get("quest", "");
    if( !empty($Quest) )
    {
        $SearchQuery = new NewSearchQuery();
        $SearchQuery->setQueryText($Quest);
        
        $Rep = $this->getSearchQueryRepository();
        $Rep->insert($SearchQuery);
        $Rep->doInsert();
    }
    
    $this->view->setVar( "quest", $Quest );
    $this->view->setVar( "andor", $this->Request->query->get("andor", "") );
    $this->view->setVar( "translit", $this->Request->query->get("translit", 0) );

    try
    {
        $SearchObject = new NewSearchObject(
                $this->Request->query->get("quest"), 
                $this->Request->query->get("andor"), 
                $this->Request->query->get("translit", 0)
            );
        $SearchObject->getResult($this->getDBObject());
        $this->view->setVar("SearchObject", $SearchObject );
    }
    catch (TRMObjectCreateException $e)
    {
        $this->view->setVar("SearchResultText", $e->getMessage() );
    }

        $Title = \GlobalConfig::$ConfigArray["SearchTitle"];
        $Description = !empty($Quest)
            ? (\GlobalConfig::$ConfigArray["SearchResultTitle"] . ": " . $Quest)
            : \GlobalConfig::$ConfigArray["SearchTitle"];

        $this->setSEO($Title, $Description, \GlobalConfig::$ConfigArray["CommonKeyWords"] . ", поиск");
        $this->view->setCanonical($this->buildAbsoluteUrl("/search"));
        $this->view->setMeta("robots", "NOINDEX,FOLLOW");
        $this->setTwitterCard("summary", array(
            "title" => $Title,
            "description" => $Description,
            "image" => $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
        ));
        $this->addSearchResultsPageJsonLd(array(
            "name" => $Title,
            "url" => $this->buildAbsoluteUrl("/search"),
            "description" => $Description,
        ));
        $this->view->setVar("PageTitle", $Title);

    return $this->view->render();
}


/**
 * поиск товаров в каталоге
 * 
 * @return string - содержимое страницы
 */
public function actionYandex()
{
    $this->view->addCss( TOPIC . "/css/search.css" , true);

        $Title = \GlobalConfig::$ConfigArray["SearchTitle"];
        $Description = \GlobalConfig::$ConfigArray["SearchTitle"];
        $KeyWords = \GlobalConfig::$ConfigArray["CommonKeyWords"] . ", поиск";

        $RawText = trim((string)$this->Request->query->get("text", ""));

        if ($RawText !== "") {
            $Title = \GlobalConfig::$ConfigArray["SearchResultTitle"] . ": " . $RawText;
            $Description = \GlobalConfig::$ConfigArray["SearchResultTitle"] . ": " . $RawText;
            $KeyWords .= ", " . $RawText;
        }

        $this->setSEO($Title, $Description, $KeyWords);
        $this->view->setCanonical($this->buildAbsoluteUrl("/search"));
        // Search page should always be closed from indexing.
        $this->view->setMeta("robots", "NOINDEX,FOLLOW");
        $this->setTwitterCard("summary", array(
            "title" => $Title,
            "description" => $Description,
            "image" => $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
        ));
        $this->addWebPageJsonLd(array(
            "name" => $Title,
            "url" => $this->buildAbsoluteUrl("/search"),
            "description" => $Description,
        ));
        $this->view->setVar("PageTitle", $Title);

    return $this->view->render();
}


} // SearchController
