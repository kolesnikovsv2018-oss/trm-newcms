<?php

declare(strict_types=1);

namespace NewCMS\Tests;

use NewCMS\Widgets\NewPagination;
use PHPUnit\Framework\TestCase;

/**
 * Чистые тесты пагинации — без обращения к $_SERVER (PrepareURL не вызывается).
 */
final class NewPaginationTest extends TestCase
{
  public function testConstructorDefaults(): void
  {
    $Pagination = new NewPagination(100, 30);

    self::assertSame(100, $Pagination->CountOfArticles);
    self::assertSame(30, $Pagination->NumOfArticlesPerPage);
    self::assertSame('page', $Pagination->PageName);
    self::assertSame(1, $Pagination->CurrentPage);
  }

  public function testSetCurrentPagesClampsToLastPage(): void
  {
    $Pagination = new NewPagination(100, 30);
    $Pagination->SetCurrentPages(99);

    // OQ-17 закрыт: количество страниц целое (int)ceil
    self::assertSame(4, $Pagination->CurrentPage);
  }

  public function testSetCurrentPagesZeroBecomesOne(): void
  {
    $Pagination = new NewPagination(100, 30);
    $Pagination->SetCurrentPages(0);

    self::assertSame(1, $Pagination->CurrentPage);
  }

  public function testSetCurrentPagesKeepsValidValue(): void
  {
    $Pagination = new NewPagination(100, 30);
    $Pagination->SetCurrentPages(2);

    self::assertSame(2, $Pagination->CurrentPage);
  }

  public function testCustomPageNameIsStored(): void
  {
    $Pagination = new NewPagination(10, 5, 'pg');

    self::assertSame('pg', $Pagination->PageName);
  }
}
