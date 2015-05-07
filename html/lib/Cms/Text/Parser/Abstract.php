<?php
/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Parser
 * @package  Text
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

abstract class Cms_Text_Parser_Abstract
{
    /**
     * Returns error text
     *
     * @return string
     */
    protected function _badFormat()
    {
        return Cms_Translate::getInstance()
            ->_('MSG_CORE_TEXT_PARSER_INCORRECT_FORMAT');
    }

    /**
     * Adds order for a query object
     * Charset: latin only
     *
     * @param object $query
     * @param string $str
     * @param array $rowList
     * @return void
     */
    protected function _appendOrder($query, $str, array $rowList)
    {
        foreach (explode(',', $str) as $mask) {
            $row = $mask = strtolower(trim($mask));
            if (strpos($mask, '-') === false) {
                continue;
            }

            $order = 'DESC';
            list($row, $order) = explode('-', $mask);
            if (!in_array($row, $rowList)) {
                continue;
            }

            $query->order($row . ' ' . strtoupper($order));
        }
    }

    /**
     * Converts string with comma into array.
     *
     * @param string $str
     * @return array
     */
    protected function _comma2list($str, $comma = ',')
    {
        if (Cms_Functions::strpos($str, $comma) === false) {
            return array($str);
        }

        $list = explode($comma, $str);
        foreach ($list as $k => &$v) {
            $v = trim($v);
            if ($v == '') {
                unset($list[$k]);
            }
            $v = Cms_Functions::strtolower($v);
        }
        return array_unique($list);
    }

    /**
     * Allow or not caching
     *
     * @return bool
     */
    protected function _isCachable()
    {
        // options check
        if (Cms_Config::getInstance()->cms->cache->disabled) {
            return false;
        }

        // admin access check
        if (Zend_Registry::get('actor')->hasAcl(CMS_ACCESS_ACP_VIEW)) {
            return false;
        }

        return true;
    }

    /**
     * Parses query string into array
     *
     * @var string $str
     * @var array $fields
     * @return array/bool
     */
    protected function _parseQuery($query, array $fields)
    {
        // cleanup &nbsp;, &#36; and etc.
        $query = preg_replace('~&.+?;~', '', $query);
        $query = preg_replace('~\s+~u', ' ', $query);

        // split into parts and correction
        $matches = $hash = array();
        if (!preg_match_all('~\s+([\w-]+):?("[^"]+?"|\d+)?~', $query, $matches, PREG_SET_ORDER)) {
            return false;
        }

        foreach ($matches as $qPart) {
            $hash[$qPart[1]] = isset($qPart[2]) ? trim($qPart[2], '"\'') : true;
        }

        foreach ($fields as $key => $val) {
            if (!isset($hash[$key])) {
                if ($val[1]) {
                    return false;
                }
                continue;
            }

            if ($val[0] && !preg_match($val[0], $hash[$key])) {
                if ($val[1]) {
                    return false;
                }
                unset($hash[$key]);
            }
        }

        return $hash;
    }
}
