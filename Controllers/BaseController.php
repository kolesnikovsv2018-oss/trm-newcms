<?php

namespace NewCMS\Controllers;

use NewCMS\Services\SEOService;
use NewCMS\Widgets\TRMCrumbs;
use NewCMS\Views\ArticlesBaseView;
use Symfony\Component\HttpFoundation\Request;
use TRMEngine\DiContainer\TRMDIContainer;
use TRMEngine\Helpers\TRMLib;
use TRMEngine\PathFinder\Exceptions\TRMControllerNotFoundedException;

/**
 * базовый контроллер с общим конструктором для большинства создаваемых контроллеров в приложении
 */
abstract class BaseController extends NewController
{
  protected SEOService $SEO;

  function __construct(Request $Request, TRMDIContainer $DIC)
  {
    parent::__construct($Request, $DIC);

    $this->view = new ArticlesBaseView($this);
    $this->SEO = new SEOService($this->view);

    if (!is_dir($this->view->getPathToViews())) {
      throw new TRMControllerNotFoundedException("Не найден вид [{$this->view->getPathToViews()}] !", 404);
    }
    //    $this->view->setVarsArray(\NewCMS\Libs\Config\NewCmsConfig::current()->getRaw());
    $this->view->setViewName(strtolower($this->CurrentActionName));
  }

  public function getSEO(): SEOService
  {
    return $this->SEO;
  }

  protected function setSEO($title, $description = null, $keywords = null): void
  {
    $this->SEO->setPageMeta($title, $description, $keywords);
  }

  protected function addJsonLd(array $data): void
  {
    $this->SEO->addJsonLd($data);
  }

  protected function addJsonLdByType(string $type, array $data = array()): void
  {
    $this->SEO->addJsonLdByType($type, $data);
  }

  protected function addWebSiteJsonLd(array $data = array()): void
  {
    $this->SEO->addWebSiteJsonLd($data);
  }

  protected function addWebPageJsonLd(array $data = array()): void
  {
    $this->SEO->addWebPageJsonLd($data);
  }

  protected function addCollectionPageJsonLd(array $data = array()): void
  {
    $this->SEO->addCollectionPageJsonLd($data);
  }

  protected function addContactPageJsonLd(array $data = array()): void
  {
    $this->SEO->addContactPageJsonLd($data);
  }

  protected function addAboutPageJsonLd(array $data = array()): void
  {
    $this->SEO->addAboutPageJsonLd($data);
  }

  protected function addSearchResultsPageJsonLd(array $data = array()): void
  {
    $this->SEO->addSearchResultsPageJsonLd($data);
  }

  protected function addWebApplicationJsonLd(array $data = array()): void
  {
    $this->SEO->addWebApplicationJsonLd($data);
  }

  protected function addCheckoutPageJsonLd(array $data = array()): void
  {
    $this->SEO->addCheckoutPageJsonLd($data);
  }

  protected function addProductJsonLd(array $data = array()): void
  {
    $this->SEO->addProductJsonLd($data);
  }

  protected function addArticleJsonLd(array $data = array()): void
  {
    $this->SEO->addArticleJsonLd($data);
  }

  protected function setTwitterCard($cardType = 'summary', array $data = array()): void
  {
    $this->SEO->setTwitterCard($cardType, $data);
  }

  protected function setOpenGraph(array $data = array()): void
  {
    $this->SEO->setOpenGraph($data);
  }

  protected function setProductSocialMeta(string $title, string $description, string $imageUrl, string $imageAlt): void
  {
    $this->SEO->setProductSocialMeta($title, $description, $imageUrl, $imageAlt);
  }

  protected function setArticleSocialMeta(
    string $title,
    string $description,
    string $imageUrl,
    string $imageAlt,
    ?string $publishedTime = null,
    ?string $modifiedTime = null,
    ?string $author = null
  ): void {
    $this->SEO->setArticleSocialMeta($title, $description, $imageUrl, $imageAlt, $publishedTime, $modifiedTime, $author);
  }

  protected function addPaginationLinks(?string $prevUrl, ?string $nextUrl): void
  {
    $this->SEO->addPaginationLinks($prevUrl, $nextUrl);
  }

  protected function buildAbsoluteUrl(string $path = ''): string
  {
    $BaseUrl = rtrim((string)(\NewCMS\Libs\Config\NewCmsConfig::current()->get('CommonURL') ?? ''), '/');

    if ($BaseUrl === '') {
      $ServerName = trim((string)filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_URL), "/\\");
      if ($ServerName !== '') {
        $BaseUrl = TRMLib::getServerProtcol() . '://' . $ServerName;
      }
    }

    if ($path === '') {
      return $BaseUrl;
    }

    return $BaseUrl . '/' . ltrim($path, '/');
  }

  protected function getOrganizationJsonLd(): array
  {
    return array(
      '@context' => 'https://schema.org',
      '@type' => 'Organization',
      'name' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyName'),
      'url' => $this->buildAbsoluteUrl('/'),
      'logo' => $this->buildAbsoluteUrl(TOPIC . '/images/logo1.gif'),
      'email' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('email'),
      'telephone' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('tel'),
      'address' => array(
        '@type' => 'PostalAddress',
        'streetAddress' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyAddress'),
        'addressLocality' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('CompanyCity'),
        'postalCode' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('PostalCode'),
        'addressCountry' => 'RU',
      ),
      'contactPoint' => array(
        '@type' => 'ContactPoint',
        'telephone' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('tel'),
        'email' => \NewCMS\Libs\Config\NewCmsConfig::current()->get('email'),
        'contactType' => 'sales',
        'availableLanguage' => 'ru',
      ),
    );
  }

  protected function addBreadcrumbJsonLd(TRMCrumbs $crumbs): void
  {
    $items = array();

    foreach ($crumbs->getOrderedItems() as $index => $item) {
      $items[] = array(
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $item['name'],
        'item' => $this->buildAbsoluteUrl($item['url']),
      );
    }

    if (!empty($items)) {
      $this->SEO->addBreadcrumbJsonLd($items);
    }
  }
} // BaseController
