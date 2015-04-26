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

class Cms_Access
{
    const MASK_ALL = '*';

    /**
     * Current instance for the singleton
     * @var Cms_Access
     */
    static protected $_instance = null;

    /**
     * User object
     * @var Cms_Db_Users_Row
     */
    protected $_user;

    /**
     * Returns singleton instance
     *
     * @return Cms_Access
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Loads user by object
     *
     * @param  mixed $obj
     * @return Cms_Access
     */
    public function setUser($obj)
    {
        switch (true) {
            case ($obj instanceof Cms_Db_Users_Row):
            case ($obj instanceof Cms_Model_User):
                $this->_user = $obj;
                break;
            default:
                throw new Cms_Exception(
                    get_class($obj) . ' is unknown data provider'
                );
        }
        return $this;
    }

    /**
     * Checks if entry has access
     *
     * @param  mixed  $alist
     * @param  string $aid (Default: Cms_Access::MASK_ALL)
     * @return bool
     */
    public function checkAccess($alist, $aid = self::MASK_ALL)
    {
        if (is_string($alist)) {
            $alist = array(
                self::MASK_ALL => array(
                    '_ACCESS' => $alist, '_ROLE' => self::MASK_ALL
                )
            );
        }

        if (null === $aid) {
            $aid = self::MASK_ALL;
        }

        if (!isset($alist[$aid])) {
            throw new Cms_Exception("Access for the '{$aid}' action not defined");
        }

        if ($this->_user->godlike) {
            return true;
        }

        if (count($alist[$aid]) > 2
            || empty($alist[$aid]['_ROLE'])
            || empty($alist[$aid]['_ACCESS'])) {
            throw new Cms_Exception('Wrong access format');
        }

        if (!is_array($alist[$aid]['_ROLE'])) {
            $alist[$aid]['_ROLE'] = array($alist[$aid]['_ROLE']);
        }

        if (!is_array($alist[$aid]['_ACCESS'])) {
            $alist[$aid]['_ACCESS'] = array($alist[$aid]['_ACCESS']);
        }

        $rolePassed = $accessPassed = false;
        for ($i=0; $i < count($alist[$aid]['_ROLE']); $i++) {
            if ($this->_allowed('role', $alist[$aid]['_ROLE'][$i])) {
                $rolePassed = true;
            }
        }

        if (!$rolePassed) {
            return false;
        }

        for ($i=0; $i < count($alist[$aid]['_ACCESS']); $i++) {
            if ($this->_allowed('access', $alist[$aid]['_ACCESS'][$i])) {
                $accessPassed = true;
            }
        }

        return $accessPassed;
    }

    /**
     * Checks if user has access
     *
     * @param  string $type
     * @param  string $name
     * @return bool
     */
    protected function _allowed($type, $name)
    {
        return $name === self::MASK_ALL ? true : in_array(
            $name, explode(',', $this->_user->{$type}), true
        );
    }
}
