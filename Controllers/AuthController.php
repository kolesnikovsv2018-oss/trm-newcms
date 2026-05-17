<?php

namespace NewCMS\Controllers;

use TRMEngine\Controller\TRMController;
use TRMEngine\Cookies\Exceptions\TRMAuthCookieException;
use TRMEngine\Cookies\TRMAuthCookie;
use Symfony\Component\HttpFoundation\Request;

abstract class AuthController extends TRMController
{
  /**
   * в этом конструкторе создается класс куки с авторизацией 
   * и если валидация пройдена, тогда продолжаем работать, иначе выход!
   */
  function __construct(Request $Request)
  {
    try {
      $cookie = new TRMAuthCookie(\GlobalConfig::$ConfigArray["AuthCookieName"] ?? "auth");
      if ($cookie) {
        $cookie->validate();
        parent::__construct($Request);
      }
    } catch (TRMAuthCookieException $e) {
      echo "Не авторизован: " . $e->getMessage();
      exit;
    }
  }
} // AuthController
