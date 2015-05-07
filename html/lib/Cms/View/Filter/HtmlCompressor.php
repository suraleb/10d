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
 * Html compressor.
 *
 * @package View
 * @subpackage Filter
 */
class Cms_View_Filter_HtmlCompressor implements Zend_Filter_Interface
{
    /**
     * Just a prefix to use during compression.
     */
    const MASK_PREFIX = '*+-Op)87*6-*6gTT97';

    /**
     * Just a suffix to use during compression.
     */
    const MASK_SUFFIX = '0(j(99)hgGH6%4~-0H';

    /**
     * List of regex for replacements
     *
     * I don't like that I have to comment these three lines, but I can't
     * find why this conversion SOMETIMES produce invalid layout in some
     * browsers.
     *
     * @var array
     */
    protected static $_replacer = array(
        '/[\n\r\t]+/' => ' ', // convert spacers into normal spaces
        '/\s{2,}/u' => '  ', // convert two-or-more spaces into two spaces
        '/\s\/\>/' => '/>', // remove spaces between tag closing bracket
        '/\<\!\-\-.*?\-\-\>/' => '', // remove comments

        // '/\s+/' => ' ', // convert multiple spaces to single
        // '/\>\s+/' => '>', // remove spaces after tags
        // '/\s+\</' => '<', // remove spaces before tags
    );

    /**
     * Defined by Zend_Filter_Interface
     *
     * Compress HTML into a long string
     *
     * @param string HTML content to be compressed
     * @return string
     */
    public function filter($html)
    {
        if (strlen($html) > ini_get('pcre.backtrack_limit')) {
            throw new Cms_Exception(
                "You should raise pcre.backtrack_limit for large HTML pages compression"
            );
        }

        // we DON'T touch contect in these tags
        $tagsToMask = array(
            'pre',
            'script',
            'style',
            'textarea'
        );

        // convert masked tags
        $masked = array();
        foreach ($tagsToMask as $tag) {
            $matches = array();
            preg_match_all('/\<' . $tag . '(.*?)\>(.*?)\<\/' . $tag . '\>/msi', $html, $matches);
            foreach ($matches[0] as $id=>$match) {
                $html = str_replace($match, self::MASK_PREFIX . $tag . $id . self::MASK_SUFFIX, $html);
                $masked[$tag . $id] = $match;
            }
        }

        // compress HTML
        $html = trim(
            preg_replace(
                array_keys(self::$_replacer),
                self::$_replacer,
                $html
            )
        );

        // deconvert masked tags from
        preg_match_all(
            '/' . preg_quote(self::MASK_PREFIX, '/') . '(\w+\d+)' . preg_quote(self::MASK_SUFFIX, '/') . '/',
            $html,
            $matches
        );

        foreach ($matches[0] as $id=>$match) {
            if (isset($masked[$matches[1][$id]])) {
                $html = str_replace($match, $masked[$matches[1][$id]], $html);
            }
        }
        return $html;
    }

}
