<?php

namespace NewCMS\Repositories\Exceptions;

class NewGroupWrongNumberException extends NewRepositoryException
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        $message .= PHP_EOL . " Неверный номер группы! " . PHP_EOL;
        parent::__construct($message, $code, $previous);
    }
}

