<?php

namespace NewCMS\MapData\Exceptions;

class NewMapDataEmptyMainObjectException extends NewMapDataException
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        $message .= PHP_EOL . " Не удалось определить основной объект для выборки! " . PHP_EOL;
        parent::__construct($message, $code, $previous);
    }
}

