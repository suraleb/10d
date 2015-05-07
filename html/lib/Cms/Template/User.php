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

class Cms_Template_User
{
    protected $_user;

    /**
     * Constructor
     *
     * @param  Cms_Model_Abstract $user
     * @return Cms_Template_User
     */
    public function __construct(Cms_Model_Abstract $user)
    {
        $this->_user = $user;
    }

    /**
     * Returns original object of the user
     *
     * @return Cms_Db_Users_Row
     */
    public function getAncestor()
    {
        return $this->_user;
    }

    /**
     * Magik function to get the property from the parent object
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getAncestor()->{$name};
    }

    /**
     * Checks if user has access
     *
     * @param  mixed  $access
     * @param  string $role (Default: null)
     * @return bool
     */
    public function hasAcl($access, $role = null)
    {
        return Cms_Access::getInstance()
            ->setUser($this->_user)
            ->checkAccess($access, $role);
    }
}
