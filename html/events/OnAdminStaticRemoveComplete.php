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
 */

class OnAdminStaticRemoveComplete extends Cms_Event_Abstract
{
    public function execute()
    {
        $logHash = array(
            'LOG_ADMIN_STATIC_REMOVE',
            Cms_Functions::substr($this->_params['title'], 0, 150),
            $this->_params['id']
        );

        $logResult = Cms_Logger::log($logHash, __CLASS__, __FUNCTION__);
        if (!$logResult) {
            $this->getResult()->fail();
        }
    }
}
