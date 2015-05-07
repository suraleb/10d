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

class Cms_Module_Admin extends Cms_Module_Abstract
{
    protected $_accessList = array(
        'run' => array(
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_ACP_VIEW
        )
    );

    public function __construct()
    {
        parent::__construct();

        $this->_tpl->addBasePath(CMS_ROOT . 'templates/backend');
        $this->_layout->setLayoutPath(CMS_ROOT . 'templates/backend/layouts');
    }

    public function __call($fnc, $args)
    {
        $dialog = Cms_Dialog::getInstance();

        $fnc = str_replace('()', '', $fnc); # php bug?
        if (!method_exists($this, $fnc)) {
            $dialog->construct(
                'MSG_ADMIN_DIALOG_ACTION_NOT_EXISTS', Cms_Dialog::TYPE_NOTICE
            );
        }

        if (!$this->_user->hasAcl($this->_accessList, $fnc)) {
            // check if we have any kind of access
            if (!$this->_user->hasAcl(CMS_ACCESS_ACP_VIEW)) {
                Cms_Core::redirect('/system/admin.html');
            }

            // display dialog
            $dialog->construct(
                'MSG_ADMIN_DIALOG_HAVENT_ACCESS', Cms_Dialog::TYPE_NOTICE
            );
        }

        $this->_applyEvents($fnc);

        return call_user_func(array(&$this, $fnc), $args);
    }

    public function getTemplate($tpl)
    {
        return str_replace(
            'Admin_', '', get_class($this)
        ) . CMS_SEP . "{$tpl}.phtml";
    }
}
