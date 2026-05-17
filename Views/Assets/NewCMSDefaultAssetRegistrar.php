<?php

namespace NewCMS\Views\Assets;

use NewCMS\Views\ArticlesBaseView;
use NewCMS\Views\BaseView;

class NewCMSDefaultAssetRegistrar implements INewCMSAssetRegistrar
{
  private INewCMSAssetUrlResolver $AssetUrlResolver;

  public function __construct(?INewCMSAssetUrlResolver $AssetUrlResolver = null)
  {
    $this->AssetUrlResolver = $AssetUrlResolver ?? new NewCMSDefaultAssetUrlResolver();
  }

  public function registerBaseAssets(BaseView $View, string $TopicWebPath, string $CmsWebPath): void
  {
    $View->addCSS($TopicWebPath . "/css/forstartpage.css", true);
    $View->addCSS($TopicWebPath . "/css/menu.css", true);
    $View->addCSS($TopicWebPath . "/css/newmenu.css", true);
    $View->addCSS($TopicWebPath . "/css/bottommenu.css", true);
    $View->addCSS($TopicWebPath . "/css/subgroups.css", true);
    $View->addCSS($TopicWebPath . "/css/forhomepage.css", true);
    $View->addCSS($TopicWebPath . "/css/article.css", true);
    $View->addCSS($TopicWebPath . "/css/pagination.css", true);
    $View->addCSS($TopicWebPath . "/css/groupslistdiv.css", true);

    $View->addJS($this->AssetUrlResolver->resolveJsUrl($CmsWebPath, 'jsglobal.js'), true);
    $View->addJS($this->AssetUrlResolver->resolveJsUrl($CmsWebPath, 'myajax.js'), true);
    $View->addJS($this->AssetUrlResolver->resolveJsUrl($CmsWebPath, 'cookies.js'));

    $View->setFavicon($TopicWebPath . "/images/favicon.svg");
  }

  public function registerArticlesAssets(ArticlesBaseView $View, string $TopicWebPath, string $CmsWebPath): void
  {
    $View->addCSS($TopicWebPath . "/css/crumbs.css", true);
    $View->addJS($this->AssetUrlResolver->resolveJsUrl($CmsWebPath, 'basket.js'));
    $View->addJS($this->AssetUrlResolver->resolveJsUrl($CmsWebPath, 'ylocation.js'));
    $View->addJS($this->AssetUrlResolver->resolveJsUrl($CmsWebPath, 'main.js'));
  }
}