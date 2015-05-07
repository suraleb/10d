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
 */

/**
 * @see Zend_Db
 */
class Cms_Db
{
    /**
     * Singleton variable
     *
     * @var bool/object
     */
    static protected $_instance = null;

    /**
     * Singleton function
     *
     * @return Zend_Db
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = Zend_Db::factory(Cms_Config::getInstance()->database);
            self::$_instance->setProfiler(CMS_DEBUG);
        }
        return self::$_instance;
    }
}
