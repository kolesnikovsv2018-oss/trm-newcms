<?php

namespace NewCMS\Views\Assets;

class NewCMSDefaultAssetUrlResolver implements INewCMSAssetUrlResolver
{
  public function resolveJsUrl(string $CmsWebPath, string $FileName): string
  {
    return rtrim($CmsWebPath, '/') . '/js/' . ltrim($FileName, '/');
  }

  public function resolveCssUrl(string $CmsWebPath, string $FileName): string
  {
    return rtrim($CmsWebPath, '/') . '/css/' . ltrim($FileName, '/');
  }
}