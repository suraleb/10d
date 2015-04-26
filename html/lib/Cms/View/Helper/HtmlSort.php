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

class Cms_View_Helper_HtmlSort extends Cms_View_Helper
{
    /**
     * Makes html link for sorting
     *
     * @param string $text
     * @param string $sortWay
     * @param string $sortId
     * @param string $link
     * @return string
     */
    public function htmlSort($text, $link, $sortId, $js = '', $disabled = false)
    {
        // disable sorting
        if ($disabled) {
            return $text;
        }

        // inputs
        $input = Cms_Input::getInstance();

        // current sort class
        $sortWay = $class = null;
        if ($input->sortby == $sortId) {
            // current sort way
            $sortWay = $input->sortway;

            // CSS class
            $class = empty($input->sorttype) ? " class='{$sortWay}' " :
                " class='{$sortWay}-{$input->sorttype}' ";
        }

        // way switch
        if (null === $sortWay) {
            $newSortWay = 'desc';
        } else {
            $newSortWay = ($sortWay == 'desc') ? 'asc' : 'desc';

            // reset way
            if ($newSortWay == 'desc' && $sortWay == 'asc') {
                $newSortWay = null;
            }
        }

        // building href html object
        if (null !== $newSortWay) {
            // append ? or & char into link
            $link .= (strpos($link, '?') === false) ? '?' : '&amp;';
            $link = "{$link}sortby={$sortId}&amp;sortway={$newSortWay}";
        }

        // output
        return "<a href='{$link}'{$class}{$js}>{$text}</a>";
    }
}
