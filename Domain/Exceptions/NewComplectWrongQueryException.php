<?php

namespace  NewCMS\Domain\Exceptions;

use TRMEngine\Exceptions\TRMException;

class NewComplectWrongQueryException extends NewComplectExceptions
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        parent::__construct("Запрос вернул ошибку! " . $message, $code, $previous);
    }
}
