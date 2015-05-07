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

class Cms_Cache_File extends Zend_Cache_Core
{
    public function __construct(
        array $backendConfig = array(), array $frontendConfig = array()
    )
    {
        $options = Cms_Config::getInstance()->cms->cache->toArray();
        $options['backend']['cache_dir'] = CMS_TMP . 'cache';

        $options['backend'] = array_merge($options['backend'], $backendConfig);
        $options['frontend'] = array_merge($options['frontend'], $frontendConfig);

        parent::__construct($options['frontend']);

        $this->setBackend(
            new Zend_Cache_Backend_File($options['backend'])
        );
    }
}
