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

class Cms_UserManager
{
    /**
     * User object
     *
     * @var Cms_User
     */
    protected $_user;

    /**
     * Singleton variable
     *
     * @var Cms_UserManager
     */
    static protected $_instance = null;

    /**
     * Exports data to the user object
     *
     * @return bool
     */
    public function save(Cms_User_Export_Interface $export)
    {
        return call_user_func(
            array($export, 'save'), $this->_user->getChangedData()
        );
    }

    /**
     * Loads user according type
     *
     * @param mixed $object
     * @return Cms_UserManager
     */
    public function recognize($object)
    {
        switch (true) {
            case ($object instanceof Cms_User):
                $this->_user = $object;
                return $this;
            case is_numeric($object):
                $class = 'Numeric';
                break;
            case ($object instanceof Cms_Auth):
                $class = 'Auth';
                break;
            default:
                throw new Cms_User_Exception("Unknown user type for import");
                break;
        }

        $user = call_user_func(array("Cms_User_Import_{$class}", 'retrieve'), $object);
        if (!($user instanceof Cms_User)) {
            return $this->recognize(0);
        }

        $this->_user = $user;
        return $this;
    }

    /**
     * Returns object for the current user
     *
     * @return Cms_User
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Updates activity of the current user
     *
     * @return void
     */
    public function activityTouch()
    {
        $this->getUser()->activity = Cms_Date::now();
        $this->save(new Cms_User_Export_Db());
    }

    /**
     * Checks if user has access
     *
     * @param array $access
     * @param string $role (Default: null)
     * @return bool
     */
    public function hasAcl($access, $role = null)
    {
        $acl = Cms_Access::getInstance();
        $acl->setUser($this->_user);

        $role = !empty($role) ? $role : '*';

        if (is_array($access)) {
            return $acl->checkAccess($access, $role);
        }

        return $acl->checkAccess(
            array($role => array('_ACCESS' => $access, '_ROLE' => $role)), $role
        );
    }

    /**
     * Magic method for extracting data from the user object
     *
     * @param string $name
     * @throws Cms_User_Exception if no attribute found
     * @return string
     */
    public function __get($name)
    {
        return $this->_user->{$name};
    }

    /**
     * Singleton
     *
     * @return Cms_UserManager
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}
