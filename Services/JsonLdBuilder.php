<?php

namespace NewCMS\Services;

class JsonLdBuilder
{
  public function build(string $type, array $data = array()): array
  {
    $type = trim($type);
    if ($type === '') {
      return array();
    }

    $payload = array(
      '@context' => 'https://schema.org',
      '@type' => $type,
    );

    foreach ($data as $key => $value) {
      if ($value === null || $value === '' || $value === array()) {
        continue;
      }
      $payload[$key] = $value;
    }

    return $payload;
  }

  public function buildBreadcrumbList(array $items = array()): array
  {
    return $this->build('BreadcrumbList', array(
      'itemListElement' => $items,
    ));
  }

  public function buildWebSite(array $data = array()): array
  {
    return $this->build('WebSite', $data);
  }

  public function buildWebPage(array $data = array()): array
  {
    return $this->build('WebPage', $data);
  }

  public function buildCollectionPage(array $data = array()): array
  {
    return $this->build('CollectionPage', $data);
  }

  public function buildContactPage(array $data = array()): array
  {
    return $this->build('ContactPage', $data);
  }

  public function buildAboutPage(array $data = array()): array
  {
    return $this->build('AboutPage', $data);
  }

  public function buildSearchResultsPage(array $data = array()): array
  {
    return $this->build('SearchResultsPage', $data);
  }

  public function buildWebApplication(array $data = array()): array
  {
    return $this->build('WebApplication', $data);
  }

  public function buildCheckoutPage(array $data = array()): array
  {
    return $this->build('CheckoutPage', $data);
  }

  public function buildBrand(?string $name): ?array
  {
    if (empty($name)) {
      return null;
    }

    return array(
      '@type' => 'Brand',
      'name' => $name,
    );
  }

  public function buildPerson(?string $name): ?array
  {
    if (empty($name)) {
      return null;
    }

    return array(
      '@type' => 'Person',
      'name' => $name,
    );
  }

  public function buildOrganizationPublisher(?string $name, ?string $logoUrl = null): ?array
  {
    if (empty($name)) {
      return null;
    }

    return $this->build('Organization', array(
      'name' => $name,
      'logo' => $logoUrl ? array(
        '@type' => 'ImageObject',
        'url' => $logoUrl,
      ) : null,
    ));
  }

  public function buildOffer(array $data = array()): ?array
  {
    if (empty($data)) {
      return null;
    }

    return $this->build('Offer', $data);
  }

  public function buildProduct(array $data = array()): array
  {
    if (isset($data['brandName']) && !isset($data['brand'])) {
      $data['brand'] = $this->buildBrand($data['brandName']);
    }

    if (isset($data['offer']) && !isset($data['offers'])) {
      $data['offers'] = $this->buildOffer($data['offer']);
    }

    unset($data['brandName'], $data['offer']);

    return $this->build('Product', $data);
  }

  public function buildArticle(array $data = array()): array
  {
    if (isset($data['authorName']) && !isset($data['author'])) {
      $data['author'] = $this->buildPerson($data['authorName']);
    }

    if (isset($data['publisherName']) && !isset($data['publisher'])) {
      $data['publisher'] = $this->buildOrganizationPublisher(
        $data['publisherName'],
        $data['publisherLogoUrl'] ?? null
      );
    }

    unset($data['authorName'], $data['publisherName'], $data['publisherLogoUrl']);

    return $this->build('Article', $data);
  }
}
