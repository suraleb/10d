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

class Cms_Functions
{
    public static function htmlspecialchars($s)
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    function html_entity_decode($s, $isHtmlSpecialChars = false)
    {
        return Utf8::html_entity_decode($s, $isHtmlSpecialChars);
    }

    function html_entity_encode($s)
    {
        return Utf8::html_entity_encode($s);
    }

    public static function strlen($s)
    {
        return Utf8::strlen($s);
    }

    public static function strpos($s, $needle, $offset = null)
    {
        return Utf8::strpos($s, $needle, $offset);
    }

    public static function strtolower($s)
    {
        return Utf8::lowercase($s);
    }

    public static function strtoupper($s)
    {
        return Utf8::uppercase($s);
    }

    public static function ltrim($s, $charlist = null)
    {
        return Utf8::ltrim($s, $charlist);
    }

    public static function rtrim($s, $charlist = null)
    {
        return Utf8::rtrim($s, $charlist);
    }

    public static function substr($s, $offset, $length = null, $inCycle = false)
    {
        return Utf8::substr($s, $offset, $length, $inCycle);
    }

    public static function ucfirst($s, $otherToLower = true)
    {
        return Utf8::ucfirst($s, $otherToLower);
    }

    public static function ucwords($s, $otherToLower = true)
    {
        return Utf8::ucwords($s, $otherToLower);
    }

    public static function textarea_rows($s, $cols, $minRows = 3, $maxRows = 32)
    {
        return Utf8::textarea_rows($s, $cols, $minRows, $maxRows);
    }

    public static function cut_string(
        $s, $len = 40, $tail = '&hellip;', &$isCutted = null, $tailMinLength = 0
    )
    {
        return Utf8::str_limit($s, $len, $tail, $isCutted, $tailMinLength);
    }

    public static function plural_form($num, $s)
    {
        $exp = explode(',', $s);
        $num = (($num < 0) ? ($num - $num * 2) : $num) % 100;
        $dig = ($num > 20) ? ($num % 10) : $num;
        return trim((($dig == 1) ? $exp[1] : (($dig > 4 || $dig < 1) ? $exp[0] : $exp[2])));
    }

    public static function strip_tags($s, array $allowableTags = null)
    {
        if (!function_exists('strip_tags_smart')) {
            require CMS_ROOT . 'lib/ThirdParty/Functions/strip_tags_smart.php';
        }
        return strip_tags_smart($s, $allowableTags);
    }

    /**
     * Escapes JavaScript string to use in html
     *
     * @param string $string
     * @return string
     */
    public static function escapeJs($string)
    {
        $string = trim($string);
        $string = str_replace("'", "\'", $string);
        $string = str_replace('"', '&quot;', $string);
        $string = str_replace(array("\r\n", "\n"), '', $string);
        return $string;
    }

    /**
     * Adds hyphen break
     *
     * @param  string $s
     * @param  bool   $isHtml (Default: false)
     * @return string
     */
    public static function hyphen_words($s, $isHtml = false)
    {
        if (!class_exists('Hyphenize', false)) {
            require CMS_ROOT . 'lib/ThirdParty/Functions/Hyphenize.php';
        }
        return Hyphenize::parse($s, $isHtml);
    }

    /**
     * Highlights word
     *
     * @param  string $s
     * @param  array  $words (Default: null)
     * @param  bool   $matchCase (Default: false)
     * @param  string $tpl
     * @return string
     */
    public static function htmlWordsHighlight(
        $s, array $words = null, $matchCase = false,
        $tpl = '<span class="highlight">%s</span>'
    )
    {
        if (!function_exists('html_words_highlight')) {
            require CMS_ROOT . 'lib/ThirdParty/Functions/html_words_highlight.php';
        }
        return html_words_highlight($s, $words, $matchCase, $tpl);
    }

    /**
     * Checks quality of the given password
     *
     * @param  string $password
     * @param  bool   $checkDigits (Default: true)
     * @param  bool   $checkLetters (Default: true)
     * @return bool
     */
    public static function passwordQualityCheck($password, $checkDigits = true, $checkLetters = true)
    {
        if (!class_exists('Password', false)) {
            require CMS_ROOT . 'lib/ThirdParty/Functions/Password.php';
        }
        return Password::quality_check($password, $checkDigits, $checkLetters);
    }

    /**
     * Generates password
     *
     * @param  mixed  $length (Default: 8)
     * @param  mixed  $chars
     * @return string
    */
    public static function passwordGenerate($length = 8,
        $chars = '23456789abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ')
    {
        if (!class_exists('Password', false)) {
            require CMS_ROOT . 'lib/ThirdParty/Functions/Password.php';
        }
        return Password::generate($length, $chars);
    }

    /**
     * Generates 3 random ANSI chars
     *
     * @param  int    $len (Default: 3)
     * @return string
     */
    public static function passwordGenerateSalt($len = 3)
    {
        $salt = '';
        for ($i = 0; $i < $len; $i++) {
            $salt .= chr(rand(32, 126));
        }
        return $salt;
    }

    /**
     * Encodes string into password for the engine
     *
     * @param  mixed  $str
     * @param  mixed  $salt
     * @return string
     */
    public static function passwordEncode($str, $salt)
    {
        return md5(md5($str) . $salt);
    }
}
