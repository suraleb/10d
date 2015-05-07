<?php
/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Data
 * @package  Library
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

/**
 * @see Zend_Config_Xml
 */
class Cms_Config extends Zend_Config_Xml
{
    /**
     * Singleton variable
     * @var Cms_Config
     */
    static protected $_instance = null;

    /**
     * Stores path to the config file
     * @var string
     */
    private $_configPath;

    /**
     * Stores section name
     * @var string
     */
    private $_currentSection = 'localhost';

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        // config path
        $this->_configPath = CMS_ROOT . 'config.xml';

        // section reset
        if (!CMS_DEBUG) {
            $this->_currentSection = 'production';
        }

        // parent constructor
        parent::__construct($this->_configPath, $this->_currentSection);
    }

    /**
     * Rebuilds config according array data
     *
     * @param array $newSettings
     * @return bool
     */
    public function saveSettings(array $newSettings)
    {
        // creating new instance of config to write data
        $currentConfig = new Zend_Config_Xml(
            $this->_configPath, null, array('allowModifications' => true)
        );

        // changing params
        $this->_assignValuesFromArray(
            $currentConfig, array($this->_currentSection => $newSettings)
        );

        // checking writing access
        if (!is_writable($this->_configPath)) {
            Cms_Filemanager::chmodSet(
                $this->_configPath, Cms_Filemanager::CHMOD_FILE_WRITE
            );
        }

        // updating config
        $writer = new Zend_Config_Writer_Xml();
        $writer->write($this->_configPath, $currentConfig, true);

        // changing chmod to secure config file
        if (!is_readable($this->_configPath)) {
            if (!Cms_Filemanager::chmodSet($this->_configPath, Cms_Filemanager::CHMOD_FILE_READ)) {
                throw new Cms_Exception(
                    'System was unable to change the access rules for the config file'
                );
            }
        }

        // reloading current config
        self::reloadInstance();

        return true;
    }

    /**
     * Changes config params according array data
     *
     * @param Zend_Config $config
     * @param array $array
     * @return void
     */
    protected function _assignValuesFromArray(Zend_Config $config, $array)
    {
        foreach ($array as $key => $value) {
            if (!isset($config->{$key})) {
                $config->{$key} = $value;
                continue;
            }

            if (is_array($value)) {
                $this->_assignValuesFromArray($config->{$key}, $value);
            } else {
                $config->{$key} = $value;
            }
        }
    }

    /**
     * Reset current instance
     *
     * @return Cms_Config
     */
    public static function reloadInstance()
    {
        self::$_instance = null;
        return self::getInstance();
    }

    /**
     * Singleton function
     *
     * @return Cms_Config
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}
