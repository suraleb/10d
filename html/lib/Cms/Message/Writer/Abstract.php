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

abstract class Cms_Message_Writer_Abstract
{
    /**
    * List of messages
    * @var Cms_Message_Abstract
    */
    protected $_message;

    /**
    * Adds message to the list
    *
    * @param Cms_Message_Abstract $msg
    * @return void
    */
    public function setMessage(Cms_Message_Abstract $msg)
    {
        $this->_message = $msg;
        return $this;
    }

    /**
    * Processes the current message according own logic
    *
    * @return void
    */
    abstract public function handle();
}
