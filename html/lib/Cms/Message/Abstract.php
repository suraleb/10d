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

class Cms_Message_Abstract implements Serializable
{
    const MSG_STATUS_SUCCESS = 'success';

    const MSG_STATUS_ERROR   = 'error';

    const MSG_STATUS_NOTICE  = 'notice';

    const MSG_STATUS_INFO    = 'info';

    protected $_message;

    protected $_status;

    public function __construct($msg, $status)
    {
        $this->setMessage($msg);
        $this->setStatus($status);
    }

    public function setMessage($text)
    {
        $this->_message = $text;
        return $this;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function setStatus($satus)
    {
        $this->_status = $satus;
        return $this;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function __toString()
    {
        return "{$this->status}: {$this->message}";
    }

    public function serialize()
    {
        return serialize(array($this->getMessage(), $this->getStatus()));
    }

    public function unserialize($str)
    {
        $data = unserialize($str);
        $this->setMessage($data[0]);
        $this->setStatus($data[1]);
    }

    public static function info($text)
    {
        return new self($text, self::MSG_STATUS_INFO);
    }

    public static function notice($text)
    {
        return new self($text, self::MSG_STATUS_NOTICE);
    }

    public static function error($text)
    {
        return new self($text, self::MSG_STATUS_ERROR);
    }

    public static function success($text)
    {
        return new self($text, self::MSG_STATUS_SUCCESS);
    }
}
