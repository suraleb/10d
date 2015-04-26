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

abstract class Cms_Event_Abstract
{
    /**
     * Params set
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Current result
     *
     * @var Cms_Event_Result
     */
    protected $_result;

    /**
     * You can't use this method
     *
     * @return void
     */
    final function __construct()
    {
        $this->_result = new Cms_Event_Result($this);
    }

    /**
     * Adds one param to the current event
     *
     * @return Cms_Event_Abstract
     */
    public function addParam($key, $value)
    {
        $this->_params[$key] = $value;
        return $this;
    }

    /**
     * Adds many params to the current event
     *
     * @param array $arraySet
     * @return Cms_Event_Abstract
     */
    public function addParams(array $arraySet)
    {
        foreach ($arraySet as $key => $value) {
            $this->addParam($key, $value);
        }
        return $this;
    }

    /**
     * Returns result for this event
     *
     * @return Cms_Event_Result
     * @see Cms_Event_Result
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Executes current action
     *
     * @return void
     */
    abstract public function execute();
}
