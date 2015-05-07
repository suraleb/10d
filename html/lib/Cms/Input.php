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
 * Handles input data
 */
class Cms_Input
{
    /**
     * Data storage
     * @var array
     */
    protected $_data;

    /**
     * Instance
     * @var Cms_Input
     */
    static protected $_instance = null;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->_data['_get']  = &$_GET;
        $this->_data['_post'] = &$_POST;
    }

    /**
     * Singleton
     *
     * @return Cms_Input
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Magic function. Data-cleanup process.
     *
     * @param  string $n
     * @return string
     */
    public function __get($n)
    {
        $v = $this->_($n);
        if ($v !== null) {
            if (is_array($v)) {
                array_walk_recursive($v, array($this, '_cleanup'));
            } else {
                return $this->_cleanup($v);
            }
        }
        return $v;
    }

    /**
     * Returns param from the storage or default value instead.
     *
     * @param string $name
     * @param mixed  $default (Default: null)
     * @param bool   $clean (Default: true)
     * @return mixed
     */
    public function getParam($name, $default = null, $clean = true)
    {
        if (!$clean) {
            return $this->_($name, $default);
        }

        $value = $this->__get($name);
        return (null === $value) ? $default : $value;
    }

    /**
     * Magic function. Places information to the storage.
     *
     * @param  string $name
     * @param  string $value
     * @return void
     */
    public function __set($name, $value)
    {
        if (isset($this->_data['_get'][$name])) {
            $this->_data['_get'][$name] = $value;
        } else {
            $this->_data['_post'][$name] = $value;
        }
    }

    /**
     * Returns plain data
     *
     * @param  string $name
     * @param  mixed  $default (Default: null)
     * @return mixed
     */
    public function _($name, $default = null)
    {
        if (isset($this->_data['_post'][$name])) {
            return $this->_data['_post'][$name];
        } elseif (isset($this->_data['_get'][$name])) {
            return $this->_data['_get'][$name];
        }
        return $default;
    }

    /**
     * Magic function. Checks if data exists.
     *
     * @param  string $name
     * @return bool
     */
    public function __isset($name)
    {
        return (
            isset($this->_data['_post'][$name])
            || isset($this->_data['_get'][$name])
        );
    }

    /**
     * Magic function. Removes data from the storage.
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->_data['_post'][$name])) {
            unset($this->_data['_post'][$name]);
        }

        if (isset($this->_data['_get'][$name])) {
            unset($this->_data['_get'][$name]);
        }
    }

    /**
     * Debug function. Returns hash of the input storage
     *
     * @param  string $type
     * @return array
     */
    public function getHash($type = null)
    {
        return ($type !== null) ? $this->_data['_' . strtolower($type)] : $this->_data;
    }

    /**
     * Checks if the current method is POST
     *
     * @return bool
     */
    public function isPost()
    {
        return ('POST' == $this->_getMethod());
    }

    /**
     * Checks if the current method is GET
     *
     * @return bool
     */
    public function isGet()
    {
        return ('GET' == $this->_getMethod());
    }

    /**
     * Checks if the current method is the ajax request
     *
     * @return bool
     */
    public function isAjax()
    {
        return (
            isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER ['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
        );
    }

    /**
     * Returns current method of a request
     *
     * @return string
     */
    protected function _getMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * XSS secure value
     *
     * @param  string $v
     * @return string
     */
    protected function _cleanup($v)
    {
        return Cms_Functions::htmlspecialchars(Cms_Functions::strip_tags($v));
    }
}
