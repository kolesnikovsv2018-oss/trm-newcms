<?php

namespace  NewCMS\Domain\Exceptions;

use TRMEngine\Exceptions\TRMException;

/**
 * должно выбрасываться, если список статей пуст
 */
class NewArticlesEmptyCollectionExceptions extends NewArticlesExceptions
{
    public function __construct( $message = "", $code = 0, \Throwable $previous = NULL)
    {
        parent::__construct("Пустой список документов! " . $message, $code, $previous);
    }
}
