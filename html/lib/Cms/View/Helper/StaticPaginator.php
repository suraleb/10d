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

class Cms_View_Helper_StaticPaginator extends Cms_View_Helper
{
    /**
     * Creates paginator for static entries
     *
     * @param int $count
     * @param int $current
     * @param string $href
     * @return string
     */
    public function staticPaginator($count, $current, $href)
    {
        if ($count < 2) {
            return false;
        }

        $view = $this->getView();
        $view->count = intval($count);
        $view->current = intval($current);
        $view->href = $href . cPages::PARSER_PAGER_ID . '=';
        return $view->render('pager-static.phtml');
    }
}
