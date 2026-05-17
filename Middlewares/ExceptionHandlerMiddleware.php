<?php

namespace NewCMS\Middlewares;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TRMEngine\PipeLine\Interfaces\MiddlewareInterface;
use TRMEngine\PipeLine\Interfaces\RequestHandlerInterface;
use TRMEngine\TRMErrorHandler;

/**
 * Посредник, который перехватывает исключения
 *
 * @author TRM
 */
class ExceptionHandlerMiddleware implements MiddlewareInterface
{
  /**
   * 
   * @param Request $Request
   * @param RequestHandlerInterface $Handler
   * @return Response
   */
  public function process(Request $Request, RequestHandlerInterface $Handler)
  {
    try {
      return $Handler->handle($Request);
    } catch (\Throwable $e) {
      // TODO - если возникает ошибка при SQL запросах,
      // то в $e->getCode() возвращается код ошибки БД,
      // а не HTTP      
      $Code = (int)$e->getCode();
      if ($Code < 100 || $Code > 599) {
        $Code = 500;
      }

      $ConfigArr = require((defined("CONFIG") ? CONFIG : "") . "/errorconfig.php");
      ob_start();
      $ErrorVars = array(
        "errstr" => "[ExceptionHandlerMiddleware -> process] " . $e->getMessage(),
        "errfile" => $e->getFile(),
        "errline" => $e->getLine(),
      );
      if (isset($ConfigArr[$Code])) {
        extract($ErrorVars);
        require $ConfigArr[$Code];
      } else {
        extract($ErrorVars);
        require $ConfigArr["commonerror"];
      }
      // if (defined("DEBUG") && DEBUG) {
      //   TRMErrorHandler::printErrorDebug(
      //     "Exception",
      //     $e->getMessage(),
      //     $e->getFile(),
      //     $e->getLine(),
      //     500, // $Code
      //   );
      // }

      return new Response(
        ob_get_clean(),
        $Code
      );
    }
  }
} // ExceptionHandlerMiddleware
