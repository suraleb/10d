<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Library
 * @package  Engine
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

abstract class Cms_Model_Abstract
{
    /**
     * Describes the current object
     * @var array
     */
    protected $_fields = array();

    /**
     * Stores changed fields
     * @var array
     */
    protected $_changedFields = array();

    /**
     * Stores name of the table class
     * @var string
     */
    protected $_tableClass;

    /**
     * Stores database object of the previous variable
     * @var object
     */
    protected $_db;

    /**
     * Current row needs to be updated or created
     * @var bool
     */
    protected $_isVirgin = true;

    /**
     * Stores validation messages of this model
     * @var array
     */
    protected $_errorMessages = array();

    /**
     * Constructor
     *
     * @param  mixed $obj (Default: null)
     * @return void
     */
    public function __construct($obj = null)
    {
        $this->_db = new $this->_tableClass();

        // we should check this class
        if (!($this->_db instanceof Cms_Db_Table_Abstract)) {
            throw new Cms_Model_Exception(
                'Class of the table should extends Cms_Db_Table_Abstract'
            );
        }

        // we should add information about fields to the storage
        foreach ($this->_db->info(Cms_Db_Users_Table::COLS) as $id) {
            $this->_fields[$id] = null;
        }

        // default values
        foreach ($this->_db->info(Cms_Db_Users_Table::METADATA) as $field => $value) {
            $this->_fields[$field] = $value['DEFAULT'];
        }

        // do we need to import user?
        if ($obj instanceof Cms_Db_Table_Row_Abstract) {
            $this->import($obj);
        }
    }

    /**
     * Checks if this model has errors
     *
     * @return bool
     */
    public function isValid()
    {
        return !count($this->_errorMessages);
    }

    /**
     * Returns error messages of this model
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_errorMessages;
    }

    /**
     * Adds error message to this model
     *
     * @param  string $msg
     * @return Cms_Model_Abstract
     */
    public function addMessage($msg)
    {
        $this->_errorMessages[] = $msg;
        return $this;
    }

    /**
     * Returns fields set that were modified
     *
     * @return array
     */
    public function getChangedFields()
    {
        return $this->_changedFields;
    }

    /**
     * If this model has modified fields, this function returns true.
     *
     * @return bool
     */
    public function hasChangedFields()
    {
        return (count($this->_changedFields) > 0);
    }

    /**
     * Returns an array of fields and their values
     *
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * Returns an array of fields (changed with original)
     *
     * @return array
     */
    public function getAllFields()
    {
        return array_merge($this->_fields, $this->_changedFields);
    }

    /**
     * Imports data from the object
     *
     * @param  Cms_Db_Table_Abstract $entry
     * @return Cms_Model_Abstract
     */
    public function import(Cms_Db_Table_Row_Abstract $entry)
    {
        $this->_isVirgin = false;

        foreach ($entry as $key => $value) {
            $this->_fields[$key] = $value;
        }

        return $this;
    }

    /**
     * This function is executed before saving
     *
     * @return void
     */
    protected function _saveBefore()
    {
        // nothing here
    }

    /**
     * This function is executed after saving
     *
     * @param  int  $result
     * @return void
     */
    protected function _saveAfter($result)
    {
        if ($this->_isVirgin) {
            $this->_fields = array_merge(
                $this->_fields, array_combine($this->_db->getPrimary(), (array) $result)
            );
        }
    }

    /**
     * Saves or updates the current entry
     *
     * @return int
     */
    public function save()
    {
        if (!$this->isValid()) {
            throw new Cms_Model_Exception("This model has errors");
        }

        $this->_saveBefore();

        $result = ($this->_isVirgin) ? $this->_create() : $this->_update();

        $this->_saveAfter($result);

        return $result;
    }

    /**
     * Magic method for extracting data
     *
     * @param  string $name
     * @return string
     * @throws Cms_User_Exception if no attribute found
     */
    public function __get($name)
    {
        // does table have such row?
        if (!array_key_exists($name, $this->_fields)) {
            throw new Cms_Model_Exception(
                get_class($this) . " doesn't have the '{$name}' field"
            );
        }

        return $this->_fields[$name];
    }

    /**
     * Magic method to set data
     *
     * @param  string $name
     * @param  mixed  $value
     * @return void
     * @throws Cms_User_Exception if no attribute found
     */
    public function __set($name, $value)
    {
        // does table have such row?
        if (!array_key_exists($name, $this->_fields)) {
            throw new Cms_Model_Exception(
                get_class($this) . " doesn't have the '{$name}' field"
            );
        }

        // we should parse the name of arg to get function
        $func = 'set' . preg_replace('~_([a-z])~e', 'ucfirst("$1")', ucfirst($name));
        if (!is_callable(array($this, $func))) {
            throw new Cms_Model_Exception("The '{$func}' method not found");
        }

        // updating marker of changed fields
        if ($this->_isVirgin) {
            $this->_fields[$name] = $value;
        } else {
            $this->_changedFields[$name] = $value;
        }
    }

    /**
     * Creates new entry in a database
     *
     * @return int
     */
    protected function _create()
    {
        $entry = $this->_db->createRow();
        foreach ($this->getFields() as $key => $value) {
            $entry->{$key} = $value;
        }
        return $entry->save();
    }

    /**
     * Updates entry in a database
     *
     * @return int
     */
    protected function _update()
    {
        $hash = $this->hasChangedFields() ? $this->getChangedFields()
            : $this->getFields();

        $primary = array();

        // we should check if tabe has only one primary
        $keys = $this->_db->getPrimary();
        if (is_array($keys)) {
            foreach ($keys as $key) {
                $primary["{$key} = ?"] = $this->_fields[$key];
            }
        }

        return $this->_db->update($hash, $primary);
    }
}
