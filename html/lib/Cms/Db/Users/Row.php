<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Library
 * @package  Engine
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

/**
 * @see Cms_Db_Table_Row_Abstract
 */
class Cms_Db_Users_Row extends Cms_Db_Table_Row_Abstract
{
    /**
     * Returns group of the user
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Returns name of the user
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
            ->setUser($this)
            ->checkAccess($access, $role);
    }
}
