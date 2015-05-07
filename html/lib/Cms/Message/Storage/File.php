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
 * @see Cms_Message_Storage_Abstract
 */
class Cms_Message_Storage_File implements Cms_Message_Storage_Interface
{
    /**
     * Filepath to write serialized data
     * @var string
     */
    protected $_file;

    protected $_messages = array();

    /**
     * Constructor
     *
     * @param  string $file
     * @return void
     */
    public function __construct($file)
    {
        if (!is_writable($file)) {
            throw new Cms_Message_Exception("The '{$file}' file is not writable");
        }

        $this->_file = $file;
        $this->_messages = $this->_loadMessages();
    }

    public function __destruct()
    {
        $this->_saveMessages();
    }

    public function store(Cms_Message_Abstract $msg)
    {
        $this->_messages[] = $msg;
        return $this;
    }

    public function fetchAll()
    {
        return $this->_messages;
    }

    public function cleanup()
    {
        $this->_messages = array();
        return $this;
    }

    protected function _loadMessages()
    {
        return unserialize(Cms_Filemanager::fileRead($this->_file));
    }

    protected function _saveMessages()
    {
        return Cms_Filemanager::fileWrite(
            $this->_file, serialize($this->_messages)
        );
    }
}
