<?php

namespace NewCMS\Controllers\AJAX;

use NewCMS\Domain\NewLiteProductForCollection;
use NewCMS\Libs\NewPrice;
use TRMEngine\Exceptions\TRMException;

/**
 * обработка AJAX-запросов для работы с внешеним прайс-листом
 */
class NewAjaxPriceController extends NewAjaxCommonController
{

public function actionAjaxGetPrice()
{
    $StartGroup = $this->Request->getContent(); //file_get_contents('php://input');
    if(!$StartGroup)
    {
        $StartGroup = \NewCMS\Libs\Config\NewCmsConfig::current()->get('StartGroup');
    }
    $filename = ROOT . "/" . \NewCMS\Libs\Config\NewCmsConfig::current()->get('pricefilename');

    $price = new NewPrice(
            $StartGroup, 
            $this->getDBObject(), 
            $this->_RM->getRepository(NewLiteProductForCollection::class)
        );
    
    if( $price->createPriceTxtFromDB($filename) )
    {
        header('Access-Control-Allow-Origin: *');
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(
                $this->Request->server->get("REQUEST_SCHEME") . "://"
                . $this->Request->server->get("SERVER_NAME") 
                . "/" . ltrim(\NewCMS\Libs\Config\NewCmsConfig::current()->get('pricefilename'), "/\\"));
    }
    else { echo "Ошибка получения прайс-листа для группы {$StartGroup}!"; }
}

public function actionAjaxPutPrice()
{
    if( $this->Request->files->get('PriceFile')===null )
    {
        throw new TRMException("Не передан файл прайс-листа!");
    }

    $simplefilename = $this->Request->files->get('PriceFile')->getPathName();

    $price = new NewPrice(
            \NewCMS\Libs\Config\NewCmsConfig::current()->get('StartGroup'), 
            $this->getDBObject() ,
            $this->_RM->getRepository(NewLiteProductForCollection::class)
        );

    try
    {
        $price->putPriceToDB($simplefilename);
    }
    catch (TRMException $e)
    {
        unlink($simplefilename);
        throw new TRMException($e->getMessage());
    }
    
    unlink($simplefilename);
}


} // NewAjaxPriceController

