<?php

namespace NewCMS\Views\Assets;

use NewCMS\Views\ArticlesBaseView;
use NewCMS\Views\BaseView;

interface INewCMSAssetRegistrar
{
  public function registerBaseAssets(BaseView $View, string $TopicWebPath, string $CmsWebPath): void;

  public function registerArticlesAssets(ArticlesBaseView $View, string $TopicWebPath, string $CmsWebPath): void;
}