<?php

namespace NewCMS\Middlewares;

use Symfony\Component\HttpFoundation\Request;
use TRMEngine\PipeLine\Interfaces\MiddlewareInterface;
use TRMEngine\PipeLine\Interfaces\RequestHandlerInterface;

/**
 * посредник, добавляет заголовок в ответ сервера 'X-Developer: TRMEngine'
 */
class StartMiddleware implements MiddlewareInterface
{
  /**
   * {@inheritDoc}
   */
  public function process(Request $Request, RequestHandlerInterface $Handler)
  {
    $Response = $Handler->handle($Request);
    $Response->headers->set('X-Developer', 'TRMEngine');
    return $Response;
  }
}
