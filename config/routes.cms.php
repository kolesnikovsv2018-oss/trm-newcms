<?php

declare(strict_types=1);

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use TRMEngine\PathFinder\TRMPathFinder;

return array(
  'core' => static function (RouteCollection $Routes): void {
    $Routes->add('mainindex', new Route(
      '/',
      array('_controller' => NewCMS\Controllers\MainController::class, 'action' => 'Index')
    ));

    $Routes->add('ajaxwidgets', new Route(
      '/AjaxWidgets/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\AjaxWidgetsController::class)
    ));

    $Routes->add('ajaximageservice', new Route(
      '/AjaxImageService/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\AjaxImageServiceController::class)
    ));

    // дерево каталога
    $Routes->add('getgrouptree', new Route(
      '/Ajax/get-group-tree',
      array(
        '_controller' => NewCMS\Controllers\AJAX\NewAjaxMenuJSONController::class,
        'action' => 'GetGroupTree'
      )
    ));

    // список характеристик
    $Routes->add('getfeatureslist', new Route(
      '/Ajax/get-features-list',
      array(
        '_controller' => NewCMS\Controllers\AJAX\NewAjaxMenuJSONController::class,
        'action' => 'GetFeaturesList'
      )
    ));

    $Routes->add('newajaxproductlist', new Route(
      '/new-ajax-product/get-products-list',
      array(
        '_controller' => NewCMS\Controllers\AJAX\NewAjaxProductController::class,
        'action' => 'GetProductsList'
      )
    ));

    $Routes->add('newajaxproduct', new Route(
      '/admin/new-ajax-product/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\NewAjaxProductController::class)
    ));

    $Routes->add('newajaxgroup', new Route(
      '/admin/new-ajax-group/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\NewAjaxGroupController::class)
    ));

    $Routes->add('newajaxarticle', new Route(
      '/admin/new-ajax-article/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\NewAjaxArticleController::class)
    ));

    $Routes->add('newajaxvendor', new Route(
      '/admin/new-ajax-vendor/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\NewAjaxVendorController::class)
    ));

    $Routes->add('newajaxnews', new Route(
      '/admin/new-ajax-news/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\NewAjaxNewsController::class)
    ));

    $Routes->add('newajaxfeature', new Route(
      '/admin/new-ajax-feature/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\NewAjaxFeatureController::class)
    ));

    $Routes->add('newajaxprice', new Route(
      '/admin/new-ajax-price/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\NewAjaxPriceController::class)
    ));

    $Routes->add('newajaxservice', new Route(
      '/admin/new-ajax-service/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\NewAjaxServiceController::class)
    ));

    $Routes->add('newajaxsender', new Route(
      '/admin/new-ajax-sender/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\NewAjaxSenderController::class)
    ));

    $Routes->add('newajaxorder', new Route(
      '/admin/new-ajax-order/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\NewAjaxOrderController::class)
    ));

    $Routes->add('newajaxquery', new Route(
      '/admin/new-ajax-query',
      array(
        '_controller' => NewCMS\Controllers\AJAX\NewAjaxQueryController::class,
        'action' => 'Start'
      )
    ));

    $Routes->add('newajaxyandex', new Route(
      '/admin/new-ajax-yandex/{action}',
      array('_controller' => NewCMS\Controllers\AJAX\NewAjaxYandexController::class)
    ));

    $Routes->add('news', new Route(
      '/news/{action}',
      array('_controller' => NewCMS\Controllers\NewsController::class, 'action' => 'Base'),
      array('action' => '[A-Za-z0-9-_]+')
    ));

    $Routes->add('about', new Route(
      '/about',
      array('_controller' => NewCMS\Controllers\MainController::class, 'action' => 'About')
    ));

    $Routes->add('contacts', new Route(
      '/contacts',
      array('_controller' => NewCMS\Controllers\MainController::class, 'action' => 'Contacts')
    ));

    $Routes->add('agreement', new Route(
      '/agreement',
      array('_controller' => NewCMS\Controllers\MainController::class, 'action' => 'Agreement')
    ));

    $Routes->add('calculator', new Route(
      '/calculator',
      array('_controller' => NewCMS\Controllers\CalculatorController::class, 'action' => 'Calculator')
    ));

    $Routes->add('search', new Route(
      '/search',
      array('_controller' => NewCMS\Controllers\SearchController::class, 'action' => 'Yandex')
    ));

    // Login, Logout
    $Routes->add('login', new Route(
      '/login/{param}',
      array('_controller' => NewCMS\Controllers\LoginController::class, 'action' => 'login', 'param' => ''),
      array('param' => '[A-Za-z0-9-_/]*')
    ));

    $Routes->add('logout', new Route(
      '/logout/{param}',
      array('_controller' => NewCMS\Controllers\LoginController::class, 'action' => 'logout', 'param' => ''),
      array('param' => '[A-Za-z0-9-_/]*')
    ));

    $Routes->add('admin', new Route(
      '/admin/{param}',
      array('_controller' => NewCMS\Controllers\AdminController::class, 'action' => 'Base', 'param' => ''),
      array('param' => '[A-Za-z0-9-_/]*')
    ));

    $Routes->add('new-basket', new Route(
      '/new-basket/{action}',
      array('_controller' => NewCMS\Controllers\NewBasketController::class, 'action' => 'Index'),
      array('action' => '[A-Za-z0-9-_]+')
    ));

    $Routes->add('message', new Route(
      '/message/{action}',
      array('_controller' => NewCMS\Controllers\MessageController::class, 'action' => 'Form'),
      array('action' => '[A-Za-z0-9-_]+')
    ));
  },
  'fallback' => static function (RouteCollection $Routes): void {
    // все остальные ...
    $Routes->add('any', new Route(
      '/{_controller}/{action}/{param}',
      array(
        '_controller' => TRMPathFinder::DefaultControllerName,
        'action' => TRMPathFinder::DefaultActionName,
        'param' => ''
      ),
      array('_controller' => '[A-Za-z0-9-_]+', 'action' => '[A-Za-z0-9-_]+', 'param' => '.*')
    ));
  }
);
