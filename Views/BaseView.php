<?php

namespace NewCMS\Views;

use NewCMS\Libs\NewCMSPathResolver;
use NewCMS\NewMenu\NewMenu;
use TRMEngine\Cache\TRMCache;
use TRMEngine\Exceptions\TRMException;
use TRMEngine\Helpers\TRMLib;

class BaseView extends CMSBaseView
{
  /**
   * @var string - строковое содержимое для отображения верхнего меню
   */
  protected $TopMenuContent;
  /**
   * @var string - строковое содержимое для отображения левого меню - каталога
   */
  protected $CatalogMenuContent;
  /**
   * @var TRMCache - объект кэша
   */
  protected $MyCache;
  /**
   * @var NewController 
   */
  protected $Controller;

  function __construct(\NewCMS\Controllers\NewController $Controller, $view = null, $layout = "")
  {
    $this->Controller = $Controller;

    //включаем кэш, в этой "сессии" он будет проверять 
    //кэшировались ли данные в файле больше часа назад, 
    //или их еще можно брать из кэша
    $this->MyCache = $Controller->getCache();

    parent::__construct($view, $layout);
  }

  public function render()
  {
    // в кеше сохраняется сериализованный объект NewMenu
    $SerialData = $this->MyCache->getCache("topgmenu");
    if (!$SerialData) {
      $topmenu = new NewMenu("Основной элемент верхнего меню", "/");

      $topmenu->TableName = "menu";
      $topmenu->IdField = "ID_Menu";
      $topmenu->ParentField = "MenuID_parent";
      $topmenu->TitleField = "MenuTitle";
      $topmenu->URLField = "MenuLink";
      $topmenu->CommentField = "Comment";
      $topmenu->PositionField = "position";
      $topmenu->PresentField = "present";
      $topmenu->ImageField = "Image";
      $topmenu->ImagePositionField = "ImagePosition";
      $topmenu->MarkField = "Mark";
      $topmenu->OrderField = "ID_Menu";

      $topmenu->getMenuFromDB($this->Controller->getDBObject());
      if (!$topmenu) {
        throw new TRMException("Не удалось прочитать данные главного меню из БД!");
      }

      $this->MyCache->setCache("topgmenu", serialize($topmenu)); // $this->TopMenuContent);
    } else {
      $topmenu = unserialize($SerialData, array('allowed_classes' => array(NewMenu::class)));
      if (!$topmenu) {
        throw new TRMException("Не удалось прочитать кешированные данные главного меню!");
      }
    }

    $this->TopMenuContent = (string)$topmenu; //ob_get_contents();

    $TopicWebPath = NewCMSPathResolver::getTopicWebPath();
    $CmsWebPath = NewCMSPathResolver::getCmsWebPath();

    NewCMSPathResolver::getAssetRegistrar()->registerBaseAssets($this, $TopicWebPath, $CmsWebPath);

    return parent::render();
  }
} // BaseView
