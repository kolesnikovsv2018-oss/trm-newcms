<?php

namespace NewCMS\Controllers;

use NewCMS\Views\CMSBaseView;

class AdminController extends AuthController
{
  public function actionBase()
  {
    $this->view = new CMSBaseView(null, null);
    return $this->view->render();
  }
} // AdminController
