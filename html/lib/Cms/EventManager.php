<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category kkcms
 * @package  Events
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

class Cms_EventManager
{
    /**
     * Events list
     *
     * @var array
     */
    private $_list = array();

    /**
     * Singleton variable
     *
     * @var Cms_EventManager
     */
    static protected $_instance = null;

    /**
     * Singleton pattern implementation makes "new" unavailable
     *
     * @return void
     */
    protected function __construct()
    {

    }

    /**
     * Singleton pattern implementation makes "clone" unavailable
     *
     * @return void
     */
    protected function __clone()
    {

    }

    /**
     * Adds event to the list
     *
     * @param object $event
     * @return Cms_EventManager
     */
    public function add(Cms_Event_Abstract $event)
    {
        $id = get_class($event);

        // check if we have this event
        if (!$this->exists($id)) {
            $this->_list[$id] = $event;
        }
        return $this;
    }

    /**
     * Checks if event in the list
     *
     * @param string $id
     * @return bool
     */
    public function exists($id)
    {
        return isset($this->_list[$id]);
    }

    /**
     * Feching event from the list
     *
     * @param string $id
     * @return Cms_Event_Abstract
     */
    public function __get($id)
    {
        if (!$this->exists($id)) {
            throw new Cms_Exception("Can't locate the '{$id}' event in the task list");
        }
        return $this->_list[$id];
    }

    /**
     * Singleton
     *
     * @return Cms_EventManager
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}
