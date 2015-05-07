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

class Cms_View_Helper_ThumbUrl extends Cms_View_Helper
{
    /**
     * Makes link to a thumb
     *
     * @param string $path
     * @param int    $width
     * @param int    $height
     * @param int    $qt
     * @param bool   $crop
     * @param string $cropPos
     * @return string
     */
    public function thumbUrl($path, $width = null, $height = null, $qt = 90, $crop = false, $cropPos = 'c')
    {
        // default path
        $url = "thumb/?q={$qt}&amp;a={$cropPos}";

        // we have width
        if ($width) {
            $url .= '&amp;w=' . intval($width);
        }

        // we have height
        if ($height) {
            $url .= '&amp;h=' . intval($height);
        }

        // crop feature
        if ($crop) {
            $url .= '&amp;zc=' . (is_bool($crop) ? 3 : intval($crop));
        }

        return $this->getView()->cmsUrl(
            Cms_View_Helper_CmsUrl::TYPE_IMAGE, $url . '&amp;src=' . $path
        );
    }
}
