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

/**
 * @see cPages
 */
require_once CMS_ROOT . 'actions/cPages.php';

class Cms_View_Helper_Paginator extends Cms_View_Helper
{
    /**
     * Creates paginator
     *
     * @param object $entries
     * @param string $hash (Default: null)
     * @param string $url (Default: null)
     * @return string
     */
    public function paginator(Zend_Paginator $entries, $hash = null, $url = null)
    {
        if (null === $url) {
            $url = @$_SERVER['REQUEST_URI'];
        }

        if (null !== $hash) {
            if (strpos($url, $hash) !== false) {
                $url = preg_replace("~(\?|&amp;|&)pager\-{$hash}=\d+~", '', $url);
            }

            $url .= (strpos($url, '?') !== false ? '&amp;' : '?') . "pager-{$hash}=";
        }

        return $this->getView()->paginationControl(
            $entries, null, null,
            array('url' => $url, 'lng' => Cms_Translate::getInstance())
        );
    }
}
