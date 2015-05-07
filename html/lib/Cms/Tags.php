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
 * Works with tags
 */
class Cms_Tags
{
    /**
     * Allowed tags mask
     * @var string
     */
    const TAG_MASK = '\p{L}\p{N}\p{Z}-';

    /**
     * Predefined symbols for the custom query
     * @var array
     */
    static protected $_charList = array(
        'or' => ',', 'and' => '+', 'not' => '~'
    );

    /**
     * Encodes tags to unicode string. Separated by $comma
     *
     * @param  string $str
     * @param  string $comma (Default: ",")
     * @return string
     */
    public static function encode($str, $comma = ',')
    {
        if (!is_array($str)) {
            $str = explode($comma, $str);
        }

        foreach ($str as $k => &$v) {
            if ($v == '') {
                unset($str[$k]);
                continue;
            }

            $v = Cms_Functions::strtolower($v);
            $v = preg_replace('~[^' . self::getRegexp() . ']~u', '', $v);
            $v = preg_replace('~\p{Z}+~u', ' ', $v);
            $v = trim($v, '- ');

            if ($v == '') {
                unset($str[$k]);
            }
        }

        return (
            count($str) ? ('[' . implode('],[', array_unique($str)) . ']') : null
        );
    }

    /**
     * Decodes unicode string to tags. Separated by $comma
     *
     * @param  string $str
     * @param  bool   $returnAsArray (Default: false)
     * @param  string $comma (Default: ",")
     * @return mixed
     */
    public static function decode($str, $returnAsArray = false, $comma = ',')
    {
        // check str
        if (empty($str)) {
            return ($returnAsArray ? array() : '');
        }

        // cleanup
        $list = (!is_array($str)) ? explode($comma, $str) : $str;
        $list = array_map(
            create_function('$v', 'return trim($v, "] [");'), $list
        );
        return ($returnAsArray ? $list : implode("{$comma} ", $list));
    }


    /**
     * Decodes tags and sort them
     *
     * @example movie+games!avatar
     * @param  string $str
     * @return array
     */
    public static function tokenize($str)
    {
        $tokens = array();
        if (empty($str)) {
            return $tokens;
        }

        $str = trim($str);
        if (!in_array(Cms_Functions::substr($str, 0, 1), array_values(self::$_charList))) {
            $str = current(array_values(self::$_charList)) . $str;
        }

        foreach (self::$_charList as $key => $char) {
            if (Cms_Functions::strpos($str, $char) === false) {
                continue;
            }

            $char = preg_quote($char, '~');

            $matches = array();
            preg_match_all("~{$char}\s*[" . self::TAG_MASK . ']+~u', $str, $matches);

            $tokens[$key] = array_map(
                create_function('$v', 'return "[" . trim(Cms_Functions::substr($v, 1)) . "]";'),
                current(array_values($matches))
            );

            if ($key == 'like') {
                $tokens[$key] = array_map(
                    create_function('$v', 'return trim($v, "][");'), $tokens[$key]
                );
            }
        }

        return $tokens;
    }

    /**
     * Creates string with regexp for tags of chars
     *
     * @param  bool $withChars (Default: false)
     * @return string
     */
    public static function getRegexp($withChars = false)
    {
        $mask = '';
        foreach (self::$_charList as $char) {
            $mask .= preg_quote($char, '~');
            if (!$withChars) {
                break;
            }
        }
        return $mask . self::TAG_MASK;
    }
}
