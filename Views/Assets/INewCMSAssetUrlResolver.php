<?php

namespace NewCMS\Views\Assets;

interface INewCMSAssetUrlResolver
{
  public function resolveJsUrl(string $CmsWebPath, string $FileName): string;

  public function resolveCssUrl(string $CmsWebPath, string $FileName): string;
}