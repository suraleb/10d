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

class OnDynamicDataChanged extends Cms_Event_Abstract
{
    public function execute()
    {
        if (!Cms_Config::getInstance()->cms->cache->disabled) {
            $ch = new Cms_Cache_File();
            $ch->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $this->_params['tags']);
        }
    }
}
