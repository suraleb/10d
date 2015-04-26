<?php
/**
 * Content Management System
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

class Cms_Session
{
    /**
     * Stores session data
     *
     * @var Zend_Session_Namespace
     */
    protected $_data;

    /**
     * Singleton
     *
     * @var Cms_Session
     */
    static protected $_instance = null;

    /**
     * Singleton pattern implementation makes "clone" unavailable
     *
     * @return void
     */
    protected function __clone()
    {

    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function __construct()
    {
        // object set for current session
        $this->_data = new Zend_Session_Namespace(__CLASS__, true);
        $this->_data->setExpirationSeconds(60 * 60 * 3);

        // security thing, see zend docs
        if (!isset($this->_data->initialized)) {
            Zend_Session::regenerateId();
            $this->_data->initialized = true;
        }

        // creating default data if needed
        if (!isset($this->_data->options)) {
            $this->_data->options = array();
        }

        if (!isset($this->_data->custom)) {
            $this->_data->custom  = array();
        }
    }

    /**
     * Returns the session namespace
     *
     * @return Zend_Session_Namespace
     */
    public function getNamespace()
    {
        return $this->_data;
    }

    /**
     * Magic function to add a custom entry to the hash
     *
     * @param string $name
     * * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->_data->custom[$name] = $value;
    }

    /**
     * Magic function to fetch the custom entry
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->_data->custom[$name])) {
            throw new Cms_Exception("Session hasn't '{$name}' member");
        }
        return $this->_data->custom[$name];
    }

    /**
     * Magic function to check that entry exist in the custom hash
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_data->custom[$name]);
    }

    /**
     * Magic function to clean entry from the custom hash
     *
     * @param string $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->_data->custom[$name]);
    }

    /**
     * Sets custom option to the session
     *
     * @param string $name
     * @param string $value
     * @return Cms_Session
     */
    public function setOption($name, $value)
    {
        if (!$this->_data->isLocked()) {
            $this->_data->options[$name] = $value;
        }
        return $this;
    }

    /**
     * Returns custom option from the session
     *
     * @param string $name
     */
    public function getOption($name)
    {
        if (isset($this->_data->options[$name])) {
            return $this->_data->options[$name];
        }
        return null;
    }

    /**
     * Singleton
     *
     * @return Cms_Session
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}
