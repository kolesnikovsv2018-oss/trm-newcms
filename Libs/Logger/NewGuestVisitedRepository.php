<?php

namespace NewCMS\Libs\Logger;

use NewCMS\Libs\Logger\NewGuestVisited;
use NewCMS\Repositories\NewRepository;
use TRMEngine\DataMapper\TRMDataMapper;
use TRMEngine\DataMapper\TRMSafetyFields;
use TRMEngine\DataSource\Interfaces\TRMDataSourceInterface;
use TRMEngine\Exceptions\TRMMySqlQueryException;

/**
 * TODO(C1-step3, DI-migration): legacy Profile L.
 * Целевой профиль: Profile A (DataSource-oriented) через NewRepository/NewIdTranslitRepository.
 * Ограничение: миграция должна быть выполнена без изменения бизнес-логики методов выборки.
 */
class NewGuestVisitedRepository extends NewRepository
{
static protected $DataObjectMap = array(
    "new_guest_visited" => array(
        TRMDataMapper::STATE_INDEX => TRMDataMapper::FULL_ACCESS_FIELD,
        TRMDataMapper::FIELDS_INDEX => array(
            "session_id" => array(
                TRMDataMapper::STATE_INDEX => TRMDataMapper::FULL_ACCESS_FIELD
            ),
        ),
    ),
);

public function __construct(TRMDataSourceInterface $DataSource)
{
    parent::__construct(NewGuestVisited::class, $DataSource);
}

/**
 * Сохраняет legacy-bootstrap DataMapper для совместимости.
 *
 * В отличие от базового NewRepository, здесь намеренно не вызывается getIdFieldName(),
 * чтобы не менять поведение существующей логики с session_id.
 *
 * @param TRMDataSourceInterface $DataSource
 * @return void
 * @throws TRMMySqlQueryException
 */
protected function initializeDataMapper(TRMDataSourceInterface $DataSource)
{
    $this->validateDataObjectMap($this->getDataObjectMap());

    $SafetyFields = new TRMSafetyFields($DataSource->getDBObject());
    $SafetyFields->setFieldsArray(static::$DataObjectMap, TRMDataMapper::READ_ONLY_FIELD);
    $SafetyFields->completeSafetyFieldsFromDB();

    $this->DataMapper = $SafetyFields;
}

/**
 * получает все просмотренные страницы для сессии
 * 
 * @param string $SessionId
 */
public function getVisitsForSession( $SessionId )
{
    $this->clearQueryParams();
    $this->DataSource->setGroupField("new_guest_visited.url");
    $this->DataSource->addWhereParam("new_guest_visited", "session_id", $SessionId);
    $Collection = $this->getAll();
    return $Collection;
}


} // NewGuestVisitedRepository

