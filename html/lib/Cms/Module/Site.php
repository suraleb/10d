<?php
/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Module
 * @package  Library
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

class Cms_Module_Site extends Cms_Module_Abstract
{
    protected $_render = true;

    /**
     * access list for a module
     * @var array
     */
    protected $_accessList = array('run' => array('_ROLE' => '*', '_ACCESS' => '*'));

    public function __construct($render = null)
    {
        parent::__construct();

        if (is_bool($render)) {
            $this->_render = $render;
        }

        $this->_tpl
            ->addBasePath(CMS_ROOT . 'templates/frontend')
            ->usedByParser = !$render;

        $this->_layout
            ->setLayoutPath(CMS_ROOT . 'templates/frontend/layouts');
    }

    public function __call($fnc, $args)
    {
        $fnc = str_replace('()', '', $fnc); # php bug?
        if (!method_exists($this, $fnc)) {
            if (!$this->_render) {
                return false;
            }
            Cms_Core::e404();
        }

        if (!$this->_user->hasAcl($this->_accessList, $fnc)) {
            if (!$this->_render) {
                return false;
            }
            Cms_Core::e403();
        }

        $this->_applyEvents($fnc);

        return call_user_func(array(&$this, $fnc), $args);
    }

    public function getTemplate($tpl)
    {
        $class = get_class($this);
        if (strpos($class, '_') !== false) {
            $class = str_replace('_c', CMS_SEP . 'c', $class);
        }
        return str_replace('Admin_', '', $class) . CMS_SEP . $tpl . '.phtml';
    }
}
