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

abstract class Cms_Firewall
{
    /**
     * Path for the ban list file
     *
     * @var string
     */
    const FIREWALL_BLACKLIST_PATH = 'system/ip.banned';

    /**
     * Checks if user's ip was blocked
     *
     * @param string $ip (Default: null)
     * @return bool
     * @see Cms_Firewall::_getBannedList()
     */
    public static function ipWasBlocked($ip = null)
    {
        return in_array(
            empty($ip) ? $_SERVER['REMOTE_ADDR'] : $ip, self::_getList()
        );
    }

    /**
     * Adds ip to the ban list
     *
     * @param string $ip
     * @return bool
     * @see Cms_Firewall::wasIpBlocked()
     */
    public static function ipUnBlock($ip = null)
    {
        if (null === $ip) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return self::_listUpdate($ip, true);
    }

    /**
     * Removes ip from the ban list
     *
     * @param string $ip
     * @return bool
     */
    public static function ipBlock($ip = null)
    {
        if (null === $ip) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return self::_listUpdate($ip);
    }

    /**
     * Updates current list of banned ips
     *
     * @see _getList
     * @param string $ip
     * @param bool $removeIt (Default: false)
     * @return bool
     */
    protected function _listUpdate($ip, $removeIt = false)
    {
        $list = self::_getList();

        // list update
        if (!$removeIt && !in_array($ip, $list)) {
            array_push($list, $ip);
        } elseif ($removeIt && in_array($ip, $list)) {
            foreach ($list as $key => $entry) {
                if ($entry == $ip) {
                    unset($list[$key]);
                }
            }
        } else {
            return false;
        }

        return Cms_Filemanager::fileWrite(
            CMS_TMP . self::FIREWALL_BLACKLIST_PATH, implode("\n", $list)
        );
    }

    /**
     * Returns list of banned ips
     *
     * @return array
     */
    protected function _getList()
    {
        $list = Cms_Filemanager::fileRead(
            CMS_TMP . self::FIREWALL_BLACKLIST_PATH
        );
        return ($list) ? explode("\n", $list) : array();
    }
}
