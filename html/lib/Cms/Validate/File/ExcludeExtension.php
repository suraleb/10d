<?php
/**
 * kkCms: Content Management System
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
 * @version  $Id$
 */

class Cms_Validate_File_ExcludeExtension extends Zend_Validate_File_ExcludeExtension
{
    public function __construct($options)
    {
        $lng = Cms_Translate::getInstance();

        $this->_messageTemplates[self::FALSE_EXTENSION] =
            $lng->_('MSG_CORE_VALIDATE_FILE_FALSE_EXTENSION');

        $this->_messageTemplates[self::NOT_FOUND] =
            $lng->_('MSG_CORE_VALIDATE_FILE_NOT_FOUND');

        parent::__construct($options);
    }
}
