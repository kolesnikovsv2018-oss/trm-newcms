<?php

namespace NewCMS\Domain\Exceptions;

use TRMEngine\Exceptions\TRMException;

class NewProductsExceptions extends TRMException
{
  public function __construct($message = "", $code = 500, \Throwable $previous = NULL)
  {
    $message .= PHP_EOL . " Ошибка при работе с сущностями Products! " . PHP_EOL;
    parent::__construct($message, $code, $previous);
  }
}







