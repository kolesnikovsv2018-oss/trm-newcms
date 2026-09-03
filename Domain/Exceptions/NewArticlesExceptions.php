<?php

namespace NewCMS\Domain\Exceptions;

use TRMEngine\Exceptions\TRMException;

class NewArticlesExceptions extends TRMException
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        $message .= PHP_EOL . " Ошибка при работе с сущностями Articles! " . PHP_EOL;
        parent::__construct($message, $code, $previous);
    }
}





