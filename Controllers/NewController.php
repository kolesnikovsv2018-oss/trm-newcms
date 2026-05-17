<?php

namespace NewCMS\Controllers;

use Symfony\Component\HttpFoundation\Request;
use TRMEngine\Cache\TRMCache;
use TRMEngine\Controller\TRMController;
use TRMEngine\DiContainer\TRMDIContainer;
use TRMEngine\Repository\TRMRepositoryManager;
use TRMEngine\TRMMySqlObject\TRMMySqlObject;

/**
 * базовый контроллер с общим конструктором для большинства создаваемых контроллеров в приложении
 */
abstract class NewController extends TRMController
{
  /**
   * @var TRMRepositoryManager - менеджер репозиториев
   */
  protected TRMRepositoryManager $_RM;
  /**
   * @var TRMDIContainer 
   */
  protected TRMDIContainer $DIC;

  /**
   * @param Request $Request
   * @param TRMDIContainer $DIC
   */
  public function __construct(Request $Request, TRMDIContainer $DIC)
  {
    parent::__construct($Request);

    $this->DIC = $DIC;
    $this->_RM = $DIC->get(TRMRepositoryManager::class);
  }

  /**
   * @return TRMDIContainer - объект кэша
   */
  public function getDIContainer()
  {
    return $this->DIC;
  }

  /**
   * @return TRMRepositoryManager
   */
  public function getRepositoryManager()
  {
    return $this->_RM;
  }


  /**
   * @return TRMCache - объект кэша
   */
  public function getCache()
  {
    return $this->DIC->get(TRMCache::class);
  }

  /**
   * @return TRMMySqlObject - объект кэша
   */
  /**
   * @return TRMMySqlObject - объект базы данных
   */
  public function getDBObject(): TRMMySqlObject
  {
    return $this->DIC->get(TRMMySqlObject::class);
  }
} // NewController
