<?php

namespace  NewCMS\Domain\Exceptions;

use TRMEngine\Exceptions\TRMException;

/**
 * должно выбрасываться, если неверно указан Id-товара, либо его нет в БД
 */
class NewProductsWrongIdExceptions extends NewProductsExceptions
{
  public function __construct($message = "", $code = 500, \Throwable $previous = NULL)
  {
    parent::__construct("Отсутсвует или не верный номер продукта в БД! " . $message, $code, $previous);
  }
}
