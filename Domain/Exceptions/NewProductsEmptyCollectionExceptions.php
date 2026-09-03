<?php

namespace  NewCMS\Domain\Exceptions;

use TRMEngine\Exceptions\TRMException;

/**
 * должно выбрасываться, если список товаров пуст
 */
class NewProductsEmptyCollectionExceptions extends NewProductsExceptions
{
  public function __construct($message = "", $code = 500, \Throwable $previous = NULL)
  {
    parent::__construct("Пустой список документов! " . $message, $code, $previous);
  }
}
