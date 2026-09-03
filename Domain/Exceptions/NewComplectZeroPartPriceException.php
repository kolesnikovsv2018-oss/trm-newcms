<?php

namespace  NewCMS\Domain\Exceptions;

use TRMEngine\Exceptions\TRMException;

class NewComplectZeroPartPriceException extends NewComplectExceptions
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        parent::__construct("Для части комплекта не задана цена. "
                . "Считать полность не имеет смысла! " . $message, $code, $previous);
    }
}
