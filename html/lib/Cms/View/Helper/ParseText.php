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

class Cms_View_Helper_ParseText
{
    /**
     * Uses internal parser to parse $query
     *
     * @param  string $text
     * @param  bool   $convert (Default: true)
     * @param  bool   $pagebreak (Default: false)
     * @return string
     */
    public function parseText($text, $convert = true, $pagebreak = false)
    {
        $parser = new Cms_Text_Parser($text);
        return $parser->getText($convert, $pagebreak);
    }
}
