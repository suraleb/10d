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

class Cms_Tag_Cloud_Decorator_HtmlTag extends Zend_Tag_Cloud_Decorator_HtmlTag
{
    protected $_htmlTags = array('strong', 'li');

    protected $_maxFontSize = 22;

    protected $_minFontSize = 12;
}
