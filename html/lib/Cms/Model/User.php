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

class Cms_Model_User extends Cms_Model_Abstract
{
    protected $_tableClass = 'Cms_Db_Users';

    /**
     * ID of the curent user
     *
     * @param  int $id
     * @return Cms_Model_User
     */
    public function setId($id)
    {
        if (!is_numeric($id) || $id < 0) {
            throw new Cms_Model_Exception('Id must be integer and positive');
        }
        $this->id = $id;
        return $this;
    }

    /**
     * Validates email for the hash
     *
     * @param  string $mail
     * @return Cms_Model_User
     */
    public function setMail($mail)
    {
        if (!Zend_Validate::is($mail, 'EmailAddress')) {
            throw new Cms_Model_Exception('Email address is not valid');
        }
        $this->mail = $mail;
        return $this;
    }

    /**
     * Generates salt for the password
     *
     * @param  string $salt (Default: null)
     * @return Cms_Model_User
     */
    public function setPasswordSalt($salt = null)
    {
        if (null === $salt) {
            $salt = Cms_Functions::passwordGenerateSalt();
        }
        $this->password_salt = $salt;
        return $this;
    }

    /**
     * Encodes password for the hash
     *
     * @param  string $pwd
     * @return Cms_Model_User
     */
    public function setPassword($pwd)
    {
        if (empty($this->_hash['password_salt'])) {
            throw new Cms_Model_Exception('To store password you need to add some salt');
        }

        $this->password = Cms_Functions::passwordEncode(
            $pwd, $this->password_salt
        );
        return $this;
    }

    /**
     * Places access to the hash
     *
     * @param  string $access
     * @return Cms_Model_User
     */
    public function setAccess($access)
    {
        $this->access = $access;
        return $this;
    }

    /**
     * Places role to the hash
     *
     * @param  string $role
     * @return Cms_Model_User
     */
    public function setRole($role)
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Name of the user
     *
     * @param  string $name
     * @return Cms_Model_User
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Options for the user
     *
     * @param  array $options
     * @return Cms_Model_User
     */
    public function setOptions(array $options)
    {
        $this->options = serialize($options);
        return $this;
    }

    /**
     * Last activity of the user
     *
     * @param  Zend_Date $date
     * @return Cms_Model_User
     */
    public function setActivity(Zend_Date $date)
    {
        $curr = new Cms_Date();
        if ($curr->isEarlier($date)) {
            throw new Cms_Model_Exception("Last activity can't be in future");
        }

        $this->activity = $date->getTimestamp();
        return $this;
    }

    /**
     * Marks this user as godlike
     *
     * @param  bool $yeah (Default: 0)
     * @return Cms_Model_User
     */
    public function setGodlike($yeah = 0)
    {
        $this->godlike = ($yeah ? '1' : '0');
        return $this;
    }
}
