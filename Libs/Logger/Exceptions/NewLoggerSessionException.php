<?php

namespace  NewCMS\Libs\Logger\Exceptions;

use TRMEngine\Exceptions\TRMException;

/**
 * выбрасывается если не удалось получить информацию об IP
 */
class NewLoggerSessionException extends \NewCMS\Libs\Logger\Exceptions\NewLoggerException
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        $message .= PHP_EOL . " Не удалось запустить сессию в NewLogger! " . PHP_EOL;
        parent::__construct($message, $code, $previous);
    }
}
