<?php

namespace NewCMS\Libs;

use NewCMS\Views\Assets\INewCMSAssetRegistrar;
use NewCMS\Views\Assets\INewCMSAssetUrlResolver;
use NewCMS\Views\Assets\NewCMSDefaultAssetRegistrar;
use NewCMS\Views\Assets\NewCMSDefaultAssetUrlResolver;

/**
 * Transitional resolver for host-dependent paths.
 *
 * Centralizes access to host paths and allows explicit overrides,
 * so NewCMS classes do not read host constants directly.
 */
class NewCMSPathResolver
{
  private static ?string $TopicWebPath = null;
  private static ?string $TopicFsPath = null;
  private static ?string $CmsWebPath = null;
  private static ?INewCMSAssetRegistrar $AssetRegistrar = null;
  private static ?INewCMSAssetUrlResolver $AssetUrlResolver = null;

  public static function setTopicPaths(string $TopicWebPath, string $TopicFsPath): void
  {
    self::$TopicWebPath = rtrim($TopicWebPath, '/');
    self::$TopicFsPath = rtrim($TopicFsPath, '/');
  }

  public static function setCmsWebPath(string $CmsWebPath): void
  {
    self::$CmsWebPath = rtrim($CmsWebPath, '/');
  }

  public static function setAssetRegistrar(INewCMSAssetRegistrar $AssetRegistrar): void
  {
    self::$AssetRegistrar = $AssetRegistrar;
  }

  public static function getAssetRegistrar(): INewCMSAssetRegistrar
  {
    if (self::$AssetRegistrar === null) {
      self::$AssetRegistrar = new NewCMSDefaultAssetRegistrar(self::getAssetUrlResolver());
    }

    return self::$AssetRegistrar;
  }

  public static function setAssetUrlResolver(INewCMSAssetUrlResolver $AssetUrlResolver): void
  {
    self::$AssetUrlResolver = $AssetUrlResolver;
  }

  public static function getAssetUrlResolver(): INewCMSAssetUrlResolver
  {
    if (self::$AssetUrlResolver === null) {
      self::$AssetUrlResolver = new NewCMSDefaultAssetUrlResolver();
    }

    return self::$AssetUrlResolver;
  }

  public static function getTopicWebPath(): string
  {
    if (self::$TopicWebPath !== null) {
      return self::$TopicWebPath;
    }

    throw new \RuntimeException(
      'NewCMSPathResolver: TopicWebPath is not configured. Inject it in host bootstrap via setTopicPaths().'
    );
  }

  public static function getTopicFsPath(): string
  {
    if (self::$TopicFsPath !== null) {
      return self::$TopicFsPath;
    }

    throw new \RuntimeException(
      'NewCMSPathResolver: TopicFsPath is not configured. Inject it in host bootstrap via setTopicPaths().'
    );
  }

  public static function getCmsWebPath(): string
  {
    if (self::$CmsWebPath !== null) {
      return self::$CmsWebPath;
    }

    throw new \RuntimeException(
      'NewCMSPathResolver: CmsWebPath is not configured. Inject it in host bootstrap via setCmsWebPath().'
    );
  }
}
