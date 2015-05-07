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
 * @package  Admin
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

class Admin_cSettings extends Cms_Module_Admin
{
    protected $_accessList = array(
        'run' => array(
            '_ACCESS' => CMS_ACCESS_SETTINGS_VIEW,
            '_ROLE'   => CMS_USER_ADMIN
        ),

        'saveAction' => array(
            '_ACCESS' => CMS_ACCESS_SETTINGS_EDIT,
            '_ROLE'   => CMS_USER_ADMIN
        )
    );

    public function run()
    {
        $this->_layout->content = $this->_tpl->render(
            $this->getTemplate(__FUNCTION__)
        );

        echo $this->_layout->render();
    }

    public function saveAction()
    {
        $newSettings = $this->_input->getHash('post');
        foreach ($newSettings as $key => $entry) {
            if (!is_array($entry)) {
                unset($newSettings[$key]);
                continue;
            }
        }

        Cms_Config::getInstance()->saveSettings($newSettings);

        // logging
        Cms_Logger::log(
            'LOG_ADMIN_SETTINGS_CONFIG_UPDATED', __CLASS__, __FUNCTION__
        );

        // redirect
        Cms_Dialog::getInstance()->construct(
            'MSG_ADMIN_SETTINGS_UPDATE_SUCCESS', Cms_Dialog::TYPE_SUCCESS,
            array('redirect' => '/open/admin-settings')
        );
    }
}
