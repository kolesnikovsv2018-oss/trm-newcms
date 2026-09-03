<?php

namespace  NewCMS\Libs\Logger\Exceptions;

use TRMEngine\Exceptions\TRMException;

/**
 * выбрасывается если не удалось получить информацию об IP
 */
class NewLoggerIpException extends \NewCMS\Libs\Logger\Exceptions\NewLoggerException
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        $message .= PHP_EOL . " Ошибка при определении IP-клиента! " . PHP_EOL;
        parent::__construct($message, $code, $previous);
    }
}
