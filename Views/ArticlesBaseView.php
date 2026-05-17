<?php

namespace NewCMS\Views;

use NewCMS\Libs\NewCMSPathResolver;

class ArticlesBaseView extends BaseView
{

  public function render()
  {
    $TopicWebPath = NewCMSPathResolver::getTopicWebPath();
    $CmsWebPath = NewCMSPathResolver::getCmsWebPath();

    NewCMSPathResolver::getAssetRegistrar()->registerArticlesAssets($this, $TopicWebPath, $CmsWebPath);

    return parent::render();
  }
} // ArticlesBaseView
