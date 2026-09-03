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

    $this->FirstTitle = \GlobalConfig::$ConfigArray["SiteName"]; //"Подвесной.РУ";
    $this->FirstLink = "/"; // \GlobalConfig::$ConfigArray["CommonURL"]; //"https://www.podvesnoi.ru/";

    $this->URLPrefix = "/" . trim(\GlobalConfig::$ConfigArray["pricePrefix"], "/");
  }

  public function __toString()
  {
    $StartGroupId = intval(\GlobalConfig::$ConfigArray["StartGroup"]);
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
