<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category engine
 * @package  Message
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

/**
 * @see Cms_Message_Storage_Interface
 */
class Cms_Message_Storage_Session implements Cms_Message_Storage_Interface
{
    /**
     * Layout instance
     * @var Zend_Session_Namespace
     */
    protected $_session;

    /**
     * Constructor
     *
     * @param Cms_Session $session
     * @return void
     */
    public function __construct()
    {
        $this->_session = new Zend_Session_Namespace(__CLASS__);
        if (!isset($this->_session->messages)) {
            $this->cleanup();
        }
    }

    public function store(Cms_Message_Abstract $msg)
    {
        $this->_session->messages[] = $msg;
        return $this;
    }

    public function fetchAll()
    {
        return $this->_session->messages;
    }

    public function cleanup()
    {
        $this->_session->messages = array();
        return $this;
    }
}
