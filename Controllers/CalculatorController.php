<?php

namespace NewCMS\Controllers;

/**
 *  контроллер для отображения групп товаров и самих товаров
 */
class CalculatorController extends BaseController
{
  public function actionCalculator()
  {
    $Title = "Расчет подвесных потолков - калькулятор на Подвесной.РУ";
    $Description = "Калькулятор подвесных потолков для расчета Армстронг, Грильято, кассетных, реечных потолков и расхода комплектующих.";

    $this->setSEO($Title, $Description, $Title);
    $this->view->setCanonical($this->buildAbsoluteUrl("/calculator"));
    $this->setTwitterCard("summary", array(
      "title" => $Title,
      "description" => $Description,
      "image" => $this->buildAbsoluteUrl(TOPIC . "/images/logo1.gif"),
    ));
    $this->addWebApplicationJsonLd(array(
      "name" => $Title,
      "url" => $this->buildAbsoluteUrl("/calculator"),
      "description" => $Description,
      "applicationCategory" => "BusinessApplication",
      "operatingSystem" => "Any",
    ));
    $this->view->setVar("PageTitle", $Title); // "Калькулятор подвесного потолка"

    $this->view->addCss((defined("TOPIC") ? TOPIC : "") . "/css/calculator.css");
    $this->view->addCSS((defined("TOPIC") ? TOPIC : "") . "/css/forcatalogpage.css", true);

    return $this->view->render();
  }
} // CalculatorController
