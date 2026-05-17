<?php

namespace NewCMS\MapData\Exceptions;

use TRMEngine\Exceptions\TRMException;

class NewMapDataException extends TRMException
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        $message .= PHP_EOL . " Ошибка работы MapData! " . PHP_EOL;
        parent::__construct($message, $code, $previous);
    }
}

