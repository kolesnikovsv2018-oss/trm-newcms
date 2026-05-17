<?php

namespace NewCMS\Services;

use TRMEngine\View\TRMView;

class SEOService
{
  protected TRMView $View;
  protected JsonLdBuilder $JsonLdBuilder;

  public function __construct(TRMView $View, ?JsonLdBuilder $JsonLdBuilder = null)
  {
    $this->View = $View;
    $this->JsonLdBuilder = $JsonLdBuilder ?: new JsonLdBuilder();
  }

  public function setPageMeta(string $title, ?string $description = null, ?string $keywords = null): void
  {
    $this->View->setTitle($title);

    if ($description !== null) {
      $this->View->setMeta('description', $this->normalizeDescription($description));
    }

    if ($keywords !== null) {
      $this->View->setMeta('keywords', $keywords);
    }
  }

  public function addJsonLd(array $data): void
  {
    $this->View->addJsonLd($data);
  }

  public function addJsonLdByType(string $type, array $data = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->build($type, $data));
  }

  public function addWebSiteJsonLd(array $data = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->buildWebSite($data));
  }

  public function addWebPageJsonLd(array $data = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->buildWebPage($data));
  }

  public function addCollectionPageJsonLd(array $data = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->buildCollectionPage($data));
  }

  public function addContactPageJsonLd(array $data = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->buildContactPage($data));
  }

  public function addAboutPageJsonLd(array $data = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->buildAboutPage($data));
  }

  public function addSearchResultsPageJsonLd(array $data = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->buildSearchResultsPage($data));
  }

  public function addWebApplicationJsonLd(array $data = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->buildWebApplication($data));
  }

  public function addCheckoutPageJsonLd(array $data = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->buildCheckoutPage($data));
  }

  public function addBreadcrumbJsonLd(array $items = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->buildBreadcrumbList($items));
  }

  public function addProductJsonLd(array $data = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->buildProduct($data));
  }

  public function addArticleJsonLd(array $data = array()): void
  {
    $this->View->addJsonLd($this->JsonLdBuilder->buildArticle($data));
  }

  public function setTwitterCard(string $cardType = 'summary', array $data = array()): void
  {
    $this->View->setTwitterMeta('card', $cardType);

    foreach ($data as $name => $value) {
      if ($value === null || $value === '') {
        continue;
      }
      $this->View->setTwitterMeta($name, $value);
    }
  }

  public function setOpenGraph(array $data = array()): void
  {
    foreach ($data as $name => $value) {
      if ($value === null || $value === '') {
        continue;
      }
      $this->View->setPropertyMeta($name, $value);
    }
  }

  public function setProductSocialMeta(string $title, string $description, string $imageUrl, string $imageAlt): void
  {
    $this->setTwitterCard('summary_large_image', array(
      'title' => $title,
      'description' => $description,
      'image' => $imageUrl,
      'image:alt' => $imageAlt,
    ));

    $this->setOpenGraph(array(
      'og:type' => 'product',
      'og:image:alt' => $imageAlt,
    ));
  }

  public function setArticleSocialMeta(
    string $title,
    string $description,
    string $imageUrl,
    string $imageAlt,
    ?string $publishedTime = null,
    ?string $modifiedTime = null,
    ?string $author = null
  ): void {
    $this->setTwitterCard('summary_large_image', array(
      'title' => $title,
      'description' => $description,
      'image' => $imageUrl,
      'image:alt' => $imageAlt,
    ));

    $this->setOpenGraph(array(
      'og:type' => 'article',
      'og:image:alt' => $imageAlt,
      'article:published_time' => $publishedTime,
      'article:modified_time' => $modifiedTime,
      'article:author' => $author,
    ));
  }

  public function addPaginationLinks(?string $prevUrl, ?string $nextUrl): void
  {
    if (!empty($prevUrl)) {
      $this->View->addLinkTag('prev', $prevUrl);
    }

    if (!empty($nextUrl)) {
      $this->View->addLinkTag('next', $nextUrl);
    }
  }

  protected function normalizeDescription(string $description): string
  {
    $description = trim(preg_replace('/\s+/u', ' ', strip_tags($description)));

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
      if (mb_strlen($description, 'UTF-8') > 160) {
        $description = rtrim(mb_substr($description, 0, 157, 'UTF-8')) . '...';
      }
      return $description;
    }

    if (strlen($description) > 160) {
      $description = rtrim(substr($description, 0, 157)) . '...';
    }

    return $description;
  }
}
