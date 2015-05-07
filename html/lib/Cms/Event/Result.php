<?php
/**
 * kkCms: Content Management System
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

/**
 * Result of a event execution
 *
 * @see Cms_Event_Abstract
 */
class Cms_Event_Result
{
    /**
     * Array with messages
     *
     * @var array
     * @see getMessages()
     */
    protected $_messages = array();

    /**
     * Total result is SUCCESS?
     *
     * @var boolean
     * @see wasSuccessful()
     */
    protected $_success = true;

    /**
     * Set total result to FAILURE
     *
     * @return void
     */
    public function fail()
    {
        $this->_success = false;
    }

    /**
     * Was the test successful?
     *
     * @return boolean
     */
    public function wasSuccessful()
    {
        return $this->_success;
    }

    /**
     * Get messages of the result
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * Add new message
     *
     * @param string Message to add
     * @return $this
     */
    public function addMessage($text)
    {
        $this->_messages[] = $text;
        return $this;
    }
}
