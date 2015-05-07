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

abstract class Cms_User_Abstract
{
    /**
     * Array with changed rows for saving
     * @var array
     */
    protected $_changedRows = array();

    /**
     * Stores information about user
     *
     * @var array
     */
    protected $_hash = array();

    /**
     * Imports information from a array
     *
     * @param Cms_Db_Table_Row_Abstract $set
     * @return Cms_User
     */
    public function import(Cms_Db_Table_Row_Abstract $set)
    {
        foreach ($set as $key => $value) {
            $this->_hash[$key] = $value;
        }
        return $this;
    }

    /**
     * Returns fiels that were modified
     *
     * @return array
     */
    public function getChangedData()
    {
        return $this->_changedRows;
    }

    /**
     * Magic method for extracting data
     *
     * @param string $name
     * @throws Cms_User_Exception if no attribute
     * @return string
     */
    public function __get($name)
    {
        if (!isset($this->_hash[$name])) {
            throw new Cms_User_Exception("User has no attribute like '{$name}'");
        }
        return $this->_hash[$name];
    }

    /**
     * Magic method to set data
     *
     * @param string $name
     * @param mixed $value
     * @throws Cms_User_Exception if no attribute
     * @return string
     */
    public function __set($name, $value)
    {
        $func = 'set';
        foreach (explode('_', $name) as $part) {
            $func .= ucfirst(strtolower($part));
        }

        if (!is_callable(array($this, $func))) {
            throw new Cms_User_Exception("The {$func} method not found");
        }

        $this->_changedRows[] = strtolower(str_replace('set', '', $name));

        call_user_func(array($this, $func), $value);
    }
}
