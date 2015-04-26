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

/**
 * @see HTMLPurifier_Filter
 */
class HTMLPurifier_Filter_Pagebreak extends HTMLPurifier_Filter
{
    const PAGEBREAK_CODE  = '<!-- pagebreak -->';
    const PAGEBREAK_TOKEN = '__PAGEBREAK__';

    public $name = 'Pagebreak';

    public function preFilter($html, $config, $context) {
        return str_replace(self::PAGEBREAK_CODE, self::PAGEBREAK_TOKEN, $html);
    }

    public function postFilter($html, $config, $context) {
        return str_replace(self::PAGEBREAK_TOKEN, self::PAGEBREAK_CODE, $html);
    }
}
