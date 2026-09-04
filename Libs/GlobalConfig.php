<?php
/**
 * Singleton для глобальных переменных конфигурации
 */

class GlobalConfig
{
/**
 * @var array - массив с параметрами конфигурации
 * array( имя параметра => значение, ... )
 */
public static $ConfigArray = array();
protected static $inst;


/**
 * @param string|null $configfilepath - путь к файлу конфигурации
 * (return array(...)); null — конфигурация уже установлена через setArray()
 */
protected function __construct($configfilepath = null)
{
    if ($configfilepath !== null)
    {
        if(!is_file($configfilepath))
        {
            throw new Exception("Файл конфигурации не найден!");
        }
        GlobalConfig::$ConfigArray = include $configfilepath;
    }

    if( empty(GlobalConfig::$ConfigArray) )
    {
        throw new Exception( "Не удалось загрузить конфигурацию![{$configfilepath}]" );
    }
}

/**
 * @param string|null $configfilepath - должен передаваться файл с содержимым типа:   return array("var1" => "value1", ...);
 * @return GlobalConfig
 */
public static function instance($configfilepath = null)
{
    if(!isset(GlobalConfig::$inst)) { GlobalConfig::$inst = new GlobalConfig($configfilepath); }
    return GlobalConfig::$inst;
}

/**
 * 2.0-transition: заполняет конфигурацию из переданных параметров.
 * Host-приложение читает свой файл конфигурации само и передаёт значения
 * явно — пакет не читает файлы и не знает путей host-приложения.
 *
 * @param array $config - массив параметров (та же структура, что в config.php)
 * @return void
 */
public static function setArray(array $config): void
{
    if( empty($config) )
    {
        throw new Exception( "Пустая конфигурация!" );
    }
    GlobalConfig::$ConfigArray = $config;
    if(!isset(GlobalConfig::$inst)) { GlobalConfig::$inst = new GlobalConfig(); }
}

} // GlobalConfig


