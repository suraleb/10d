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

class Cms_View_Helper_RenderStatic
{
    /**
     * Gets page from the database. By name.
     * Returns html according params.
     *
     * @param  string $name
     * @param bool $convert (Default: true)
     * @param bool $pagebreak (Default: false)
     * @return string
     */
    public function renderStatic($name, $convert = true, $pagebreak = false)
    {
        $db = new Cms_Db_Static();

        // db get
        $entry = $db->fetchRow(
            $db->select()->from($db, array('content'))
            ->where('active = ?', '1')
            ->where('rewrite = ?', $name)
        );

        // exist check
        if (!count($entry)) {
            return null;
        }

        // parsing and return
        $parser = new Cms_Text_Parser($entry->content);
        return $parser->getText($convert, $pagebreak);
    }
}
