<?php

namespace NewCMS\Widgets;

use TRMEngine\TRMMySqlObject\TRMMySqlObject;

/**
 * хлебные крошки для статей и других документов
 */
class ArticleCrumbs extends TRMCrumbs
{

  public function __construct()
  {
    $this->TableName = "`articlestype`";
    $this->IdField = "`ID_articlestype`";
    $this->ParentField = "";
    $this->TitleField = "ArticlesTypeName";
    $this->URLField = "ArticlesURL";

    $this->FirstTitle = \GlobalConfig::$ConfigArray["SiteName"]; //"Подвесной.РУ";
    $this->FirstLink = "/";

    $this->URLPrefix = ""; // "/" . trim(\GlobalConfig::$ConfigArray["articlesListPrefix"], "/");
  }
} // ArticleCrumbs
