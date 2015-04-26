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

class Cms_User extends Cms_User_Abstract
{
    /**
     * ID of the curent user
     *
     * @param int $id
     * @return Cms_User
     */
    public function setId($id)
    {
        if (!is_numeric($id) || $id < 0) {
            throw new Cms_User_Exception('Id must be integer and positive');
        }
        $this->_hash['id'] = $id;
        return $this;
    }

    /**
     * Validates email for the hash
     *
     * @param string $mail
     * @return Cms_User
     */
    public function setMail($mail)
    {
        if (!Zend_Validate::is($mail, 'EmailAddress')) {
            throw new Cms_User_Exception('Email address is not valid');
        }
        $this->_hash['mail'] = $mail;
        return $this;
    }

    /**
     * Generates salt for the password
     *
     * @param string $salt (Default: null)
     * @return Cms_User
     */
    public function setPasswordSalt($salt = null)
    {
        if (null === $salt) {
            $salt = Cms_Functions::passwordGenerateSalt();
        }
        $this->_hash['password_salt'] = $salt;
        return $this;
    }

    /**
     * Encodes password for the hash
     *
     * @param string $pwd
     * @return Cms_User
     */
    public function setPassword($pwd)
    {
        if (empty($this->_hash['password_salt'])) {
            throw new Cms_User_Exception('To store password you need to add some salt');
        }

        $this->_hash['password'] = Cms_Functions::passwordEncode(
            $pwd, $this->_hash['password_salt']
        );
        return $this;
    }

    /**
     * Places access to the hash
     *
     * @param string $access
     * @return Cms_User
     */
    public function setAccess($access)
    {
        $this->_hash['access'] = $access;
        return $this;
    }

    /**
     * Places role to the hash
     *
     * @param string $role
     * @return Cms_User
     */
    public function setRole($role)
    {
        $this->_hash['role'] = $role;
        return $this;
    }

    /**
     * Name of the user
     *
     * @param  string $name
     * @return Cms_User
     */
    public function setName($name)
    {
        $this->_hash['name'] = $name;
        return $this;
    }

    /**
     * Options for the user
     *
     * @param  array $options
     * @return Cms_User
     */
    public function setOptions(array $options)
    {
        $this->_hash['options'] = serialize($options);
        return $this;
    }

    /**
     * Last activity of the user
     *
     * @param  Zend_Date $date
     * @return Cms_User
     */
    public function setActivity(Zend_Date $date)
    {
        $curr = new Cms_Date();
        if ($curr->isEarlier($date)) {
            throw new Cms_User_Exception("Last activity can't be in future");
        }

        $this->_hash['activity'] = $date->getTimestamp();
        return $this;
    }

    /**
     * Marks this user as godlike
     *
     * @param  bool $yeah (Default: 0)
     * @return Cms_User
     */
    public function setGodlike($yeah = 0)
    {
        $this->_hash['godlike'] = ($yeah ? '1' : '0');
        return $this;
    }
}
