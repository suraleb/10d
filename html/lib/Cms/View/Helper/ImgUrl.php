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

class Cms_View_Helper_ImgUrl extends Cms_View_Helper
{
    /**
     * Makes link to the image file
     *
     * @param  string|int $name
     * @return string
     */
    public function imgUrl($name)
    {
        return $this->getView()->cmsUrl('img', $name);
    }
}
