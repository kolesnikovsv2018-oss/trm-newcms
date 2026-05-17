<?php

namespace NewCMS\Repositories;

use NewCMS\Domain\NewArticle;
use TRMEngine\DataMapper\TRMDataMapper;
use TRMEngine\DataSource\Interfaces\TRMDataSourceInterface;
use TRMEngine\Exceptions\TRMMySqlQueryException;

class NewArticleRepository extends NewIdTranslitRepository
{
static protected $DataObjectMap = array(
    "articles" => array(
        TRMDataMapper::STATE_INDEX => TRMDataMapper::FULL_ACCESS_FIELD,
        TRMDataMapper::FIELDS_INDEX => array(
            "ID_article" => array(
                TRMDataMapper::KEY_INDEX => "PRI",
            ),
            "Reserv" => array(
                TRMDataMapper::RELATION_INDEX => array( TRMDataMapper::OBJECT_NAME_INDEX => "articlestype", 
                                                           TRMDataMapper::FIELD_NAME_INDEX => "ID_articlestype" ),
            )
        ),
    ),
    "articlestype" => array(
        TRMDataMapper::STATE_INDEX => TRMDataMapper::READ_ONLY_FIELD,
        TRMDataMapper::FIELDS_INDEX => array(
            "ID_articlestype" => array(
                TRMDataMapper::KEY_INDEX => "PRI",
            ),
        ),
        
    )
);

public function __construct(TRMDataSourceInterface $DataSource)
{
    parent::__construct(NewArticle::class, $DataSource);
}

/**
 * @param int $CurrentTypeId - ID-типа документа, количество статей которого нужно подсчитать в БД
 * @return int - возвращает общее количество документов типа
 * @throws TRMMySqlQueryException
 */
public function getCountOfArticlesOfCurrentType(int $CurrentTypeId): int
{
    $Query = $this->buildCountArticlesQuery($CurrentTypeId);
    $Res = $this->DataSource->getDBObject()->query($Query);
    if (!$Res) {
        throw new TRMMySqlQueryException("Не удалось получить документы типа: {$CurrentTypeId}");
    }
    $Row = $Res->fetch_array(MYSQLI_NUM);
    return isset($Row[0]) ? (int)$Row[0] : 0;
}

/**
 * Строит безопасный COUNT-запрос для статей по типу.
 * Параметр типизирован и безопасен от injection.
 *
 * @param int $ArticleTypeId
 * @return string
 */
protected function buildCountArticlesQuery(int $ArticleTypeId): string
{
    // Значение типизировано через int (безопасно от injection).
    return "SELECT count(`ID_article`) FROM `articles` WHERE `Reserv` = {$ArticleTypeId}";
}


/**
 * @param string $Uri - URI документа для поиска типа
 * @return int - количество статей найденного типа
 * @throws TRMMySqlQueryException
 */
public function getCountOfArticlesForUri(string $Uri): int
{
    $Query = $this->buildSelectArticleTypeByUriQuery($Uri);
    $Res = $this->DataSource->getDBObject()->query($Query);
    if (!$Res) {
        throw new TRMMySqlQueryException("Не найдены документы с URI: {$Uri}");
    }
    $Row = $Res->fetch_array(MYSQLI_NUM);
    if (!isset($Row[0])) {
        throw new TRMMySqlQueryException("Пустой результат SELECT для URI: {$Uri}");
    }
    return $this->getCountOfArticlesOfCurrentType((int)$Row[0]);
}

/**
 * Строит безопасный SELECT-запрос для поиска типа статьи по URI.
 * Параметр экранируется через real_escape_string (safe).
 *
 * @param string $Uri
 * @return string
 */
protected function buildSelectArticleTypeByUriQuery(string $Uri): string
{
    $DBObject = $this->DataSource->getDBObject();
    $EscapedUri = $DBObject && isset($DBObject->newlink) && $DBObject->newlink
        ? $DBObject->newlink->real_escape_string($Uri)
        : addslashes($Uri);
    return "SELECT `ID_articlestype` FROM `articlestype` WHERE `ArticlesURL`='{$EscapedUri}'";
}


} // NewArticleRepository


