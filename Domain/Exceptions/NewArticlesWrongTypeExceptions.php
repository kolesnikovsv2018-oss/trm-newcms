<?php

namespace  NewCMS\Domain\Exceptions;

use TRMEngine\Exceptions\TRMException;

/**
 * должно выбрасываться, если не найдкн тип документов
 */
class NewArticlesWrongTypeExceptions extends NewArticlesExceptions
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        parent::__construct("Отсутсвует или не верный номер типа документов! " . $message, $code, $previous);
    }
}
