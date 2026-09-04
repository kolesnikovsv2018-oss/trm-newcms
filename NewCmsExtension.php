<?php

namespace NewCMS;

use NewCMS\Libs\Config\NewCmsConfig;
use NewCMS\Libs\NewCMSPathResolver;

/**
 * 2.0: расширение NewCMS — единственная точка настройки пакета.
 *
 * Host-приложение читает свой конфигурационный файл само и передаёт
 * значения параметрами:
 *
 *   (new NewCmsExtension($Params))->register();
 *
 * Первый слайс релиза C (dual-mode): параметры заполняют легаси-хранилище
 * GlobalConfig (все потребители 1.x продолжают работать), theme-пути
 * прописываются в NewCMSPathResolver. В финале 2.0 хранилище GlobalConfig
 * будет удалено, потребители перейдут на NewCmsConfig напрямую.
 */
final class NewCmsExtension
{
    /** @var NewCmsConfig */
    private $Config;

    public function __construct(array $Params)
    {
        $this->Config = new NewCmsConfig($Params);
    }

    public function getConfig(): NewCmsConfig
    {
        return $this->Config;
    }

    /**
     * Регистрирует конфигурацию пакета.
     * Не читает файлы, не использует superglobals и host-константы.
     */
    public function register(): void
    {
        // текущая типизированная конфигурация (для NewCmsConfig::current())
        NewCmsConfig::setCurrent($this->Config);

        // легаси-хранилище (потребители 1.x) — заполняется из параметров
        \GlobalConfig::setArray($this->Config->getRaw());

        // theme-пути — в typed-резолвер (инъекция вместо констант)
        if ($this->Config->getTopicName() !== '') {
            $TopicWebPath = '/topics/' . $this->Config->getTopicName();

            $TopicFsPath = $this->Config->getTopicFsPath();
            if ($TopicFsPath !== '') {
                NewCMSPathResolver::setTopicPaths($TopicWebPath, $TopicFsPath);
            }
        }

        $CmsWebPath = $this->Config->getCmsWebPath();
        if ($CmsWebPath !== '') {
            NewCMSPathResolver::setCmsWebPath($CmsWebPath);
        }
    }
}
