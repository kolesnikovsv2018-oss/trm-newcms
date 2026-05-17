<?php

namespace NewCMS\MapData\Exceptions;

class NewMapDataEmptyIdFieldException extends NewMapDataException
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        $message .= PHP_EOL . " Не удалось определить имя поля содержащее Id! " . PHP_EOL;
        parent::__construct($message, $code, $previous);
    }
}

