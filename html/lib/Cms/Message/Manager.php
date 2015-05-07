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

class Cms_Message_Manager implements Iterator, Countable
{
    /**
     * A pointer for the iterator to keep track of the message array
     * @var int
     */
    protected $_messagesKey = 0;

    /**
     * Set of messages
     * @var array
     */
    protected $_messages = array();

    /**
     * Set of writers
     * @var array
     */
    protected $_writers = array();

    /**
     * Storage object
     * @var Cms_Message_Storage_Interface
     */
    protected $_storage;

    /**
     * Adds writer to the list of writers
     *
     * @param  Cms_Message_Writer_Abstract $writer
     * @return Cms_Message_Manager
     */
    public function addWriter(Cms_Message_Writer_Abstract $writer)
    {
        $this->_writers[] = $writer;
        return $this;
    }

    /**
     * Returns list of writers for a message
     *
     * @return array
     */
    public function getWriters()
    {
        return $this->_writers;
    }

    /**
     * Adds message to the list of messages
     *
     * @param  Cms_Message_Abstract $msg
     * @return Cms_Message_Manager
     */
    public function addMessage(Cms_Message_Abstract $msg)
    {
        $this->_messages[] = $msg;
        return $this;
    }

    /**
     * Returns list of messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * Sets storage for the messages
     *
     * @param Cms_Message_Storage_Interface $storage
     */
    public function setStorage(Cms_Message_Storage_Interface $storage)
    {
        $this->_storage = $storage;
        return $this;
    }

    /**
     * Returns storage engine for messages
     *
     * @return Cms_Message_Storage_Interface
     */
    public function getStorage()
    {
        return $this->_storage;
    }

    /**
     * Stores message for future use
     *
     * @param  Cms_Message_Abstract $msg
     * @return Cms_Message_Manager
     */
    public function store(Cms_Message_Abstract $msg)
    {
        if (!$this->_storage) {
            throw new Cms_Message_Exception('Storage engine is not set');
        }
        $this->_storage->store($msg);
        return $this;
    }

    /**
     * Notices writers to parse messages
     *
     * @param  $storageCleanup (Default: false)
     * @return Cms_Message_Manager
     */
    public function dispatch($storageCleanup = false)
    {
        if ($this->_storage) {
            $this->_messages = array_merge(
                $this->_messages, $this->_storage->fetchAll()
            );

            if ($storageCleanup) {
                $this->_storage->cleanup();
            }
        }

        foreach ($this->_writers as $writer) {
            foreach ($this->_messages as $msg) {
                $writer->setMessage($msg)->handle();
            }
        }

        return $this;
    }

    /**
     * Get the number of message entries.
     * Required by the Iterator interface.
     *
     * @return int
     */
    public function count()
    {
        return count($this->_messages);
    }

    /**
     * Reset the pointer in the manager object
     *
     * @return void
     */
    function rewind()
    {
        $this->_messagesKey = 0;
    }

    /**
     * Return the current message
     *
     * @return Cms_Message_Abstract
     */
    function current()
    {
        return $this->_messages[$this->_messagesKey];
    }

    /**
     * Return the current manager key
     *
     * @return unknown
     */
    function key()
    {
        return $this->_messagesKey;
    }

    /**
     * Move the manager pointer forward
     *
     * @return void
     */
    function next()
    {
        ++$this->_messagesKey;
    }

    /**
     * Check to see if the iterator is still valid
     *
     * @return boolean
     */
    function valid()
    {
        return isset($this->_messages[$this->_messagesKey]);
    }
}
