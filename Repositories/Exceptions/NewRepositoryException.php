<?php

namespace NewCMS\Repositories\Exceptions;

use TRMEngine\Exceptions\TRMException;

class NewRepositoryException extends TRMException
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        $message .= PHP_EOL . " Ошибка при работе с репозиторием в NewCMS! " . PHP_EOL;
        parent::__construct($message, $code, $previous);
    }
}

