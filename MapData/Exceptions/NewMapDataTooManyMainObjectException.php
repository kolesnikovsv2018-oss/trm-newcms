<?php

namespace NewCMS\MapData\Exceptions;

class NewMapDataTooManyMainObjectException extends NewMapDataException
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        $message .= PHP_EOL . " Объектов много и выбрать из них главный не удается! " . PHP_EOL;
        parent::__construct($message, $code, $previous);
    }
}

