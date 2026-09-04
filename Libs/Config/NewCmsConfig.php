<?php

namespace NewCMS\Libs\Config;

/**
 * 2.0: типизированная обёртка над параметрами NewCmsExtension.
 *
 * Источник значений — параметры, переданные host-приложением
 * (site читает свой config.php сам и передаёт массив явно).
 * Пакет не читает файлы и не знает путей host-приложения.
 */
final class NewCmsConfig
{
    /** @var NewCmsConfig|null — текущая конфигурация (после register()) */
    private static ?NewCmsConfig $Current = null;

    /** @var array */
    private $Params;

    public function __construct(array $Params)
    {
        $this->Params = $Params;
    }

    /** Регистрирует конфигурацию как текущую (вызывается NewCmsExtension::register()) */
    public static function setCurrent(self $Config): void
    {
        self::$Current = $Config;
    }

    /** Текущая конфигурация (после NewCmsExtension::register()) */
    public static function current(): self
    {
        if (self::$Current === null) {
            throw new \RuntimeException(
                'NewCmsConfig is not registered — call NewCmsExtension::register() first'
            );
        }
        return self::$Current;
    }

    /** @return array — исходный массив параметров (для легаси-хранилища) */
    public function getRaw(): array
    {
        return $this->Params;
    }

    /** @param mixed $Default */
    public function get(string $Key, $Default = null)
    {
        return $this->Params[$Key] ?? $Default;
    }

    public function getSiteName(): string
    {
        return (string)$this->get('SiteName', '');
    }

    public function getCommonTitle(): string
    {
        return (string)$this->get('CommonTitle', '');
    }

    public function getCommonDescription(): string
    {
        return (string)$this->get('CommonDescription', '');
    }

    public function getCommonKeyWords(): string
    {
        return (string)$this->get('CommonKeyWords', '');
    }

    public function getStartGroup(): int
    {
        return (int)$this->get('StartGroup', 0);
    }

    public function getGlobalStartGroup(): int
    {
        return (int)$this->get('GlobalStartGroup', 0);
    }

    public function getPricePrefix(): string
    {
        return (string)$this->get('pricePrefix', 'price');
    }

    public function getCatalogPrefix(): string
    {
        return (string)$this->get('catalogPrefix', 'catalog');
    }

    public function getArticlesListPrefix(): string
    {
        return (string)$this->get('articlesListPrefix', 'articles');
    }

    public function getCharset(): string
    {
        return (string)$this->get('Charset', 'utf-8');
    }

    public function getTopicName(): string
    {
        return (string)$this->get('TopicName', '');
    }

    public function getImageCatalog(): string
    {
        return (string)$this->get('ImageCatalog', '');
    }

    public function getBasketTitle(): string
    {
        return (string)$this->get('BasketTitle', '');
    }

    public function getCompanyName(): string
    {
        return (string)$this->get('CompanyName', '');
    }

    /** Файловый путь темы (задаётся host-приложением явно) */
    public function getTopicFsPath(): string
    {
        return (string)$this->get('topic_fs_path', '');
    }

    /** Web-путь самого пакета (легаси: до удаления WEB/FULLWEB в релизе D) */
    public function getCmsWebPath(): string
    {
        return (string)$this->get('cms_web_path', '');
    }

    /** Корень host-проекта (задаётся host-приложением явно; для файлов прайса/каталогов) */
    public function getProjectRoot(): string
    {
        return (string)$this->get('project_root', '');
    }

    /** Имя URL-параметра пагинации */
    public function getPaginationParameter(): string
    {
        return (string)$this->get('pagination_parameter', 'page');
    }
}
