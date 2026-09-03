<?php

namespace  NewCMS\Domain\Exceptions;

use TRMEngine\Exceptions\TRMException;

/**
 * должно выбрасываться, если из cookie файла не удалось получить список товаров
 */
class NewProductsWrongCookieExceptions extends NewProductsExceptions
{
  public function __construct($message = "", $code = 500, \Throwable $previous = NULL)
  {
    parent::__construct("Ошибка при работе с Cookie-файлом товаров! " . $message, $code, $previous);
  }
}
