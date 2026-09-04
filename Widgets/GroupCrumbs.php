<?php

namespace NewCMS\Widgets;

use TRMEngine\TRMMySqlObject\TRMMySqlObject;

/**
 * хлебные крошки для каталога
 */
class GroupCrumbs extends TRMCrumbs
{

  public function __construct()
  {
    $this->TableName = "`group`";
    $this->IdField = "ID_Group";
    $this->ParentField = "GroupID_parent";
    $this->TitleField = "GroupTitle";
    $this->URLField = "GroupTranslit";

    $this->FirstTitle = \NewCMS\Libs\Config\NewCmsConfig::current()->get('SiteName'); //"Подвесной.РУ";
    $this->FirstLink = "/"; // \NewCMS\Libs\Config\NewCmsConfig::current()->get('CommonURL'); //"https://www.podvesnoi.ru/";

    $this->URLPrefix = "/" . trim(\NewCMS\Libs\Config\NewCmsConfig::current()->get('pricePrefix'), "/");
  }

  public function __toString()
  {
    $StartGroupId = intval(\NewCMS\Libs\Config\NewCmsConfig::current()->get('StartGroup'));
    foreach ($this->Crumbs as $Key => $Crumb) {
      if (
        isset($Crumb[$this->IdField]) &&
        intval($Crumb[$this->IdField]) === $StartGroupId
      ) {
        $this->Crumbs[$Key][$this->URLField] = null;
        break;
      }
    }
    return parent::__toString();
  }
} // GroupCrumbs
