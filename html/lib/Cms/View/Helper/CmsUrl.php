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

class Cms_View_Helper_CmsUrl
{
    const TYPE_IMAGE  = 'i';
    const TYPE_SCRIPT = 'js';
    const TYPE_CSS    = 'css';

    /**
     * Converts path to the subdomain format
     *
     * @param  string $type
     * @param  string $target
     * @return string
     */
    public function CmsUrl($type, $target)
    {
        $url = CMS_HOST;

        $target = preg_replace('~\s*~', '', $target);
        $config = Cms_Config::getInstance()->cms->domains->toArray();
        if (!isset($config[$type])) {
            return ($url . '/' . $target);
        }

        if (!$config['disabled'] && !empty($config[$type]['name'])) {
            $host = preg_replace('~^www\.~', '', $_SERVER['HTTP_HOST']);
            foreach ($config as $v) {
                if (isset($v['name'])) {
                    $host = preg_replace('~^' . preg_quote($v['name'], '~') . '\.~', '', $host);
                }
            }
            $url = 'http' . (CMS_HTTPS ? 's' : '') . "://{$config[$type]['name']}.{$host}";
        }

        if ($target{0} == '/') {
            return ($url . $target);
        }

        return ($url . $config[$type]['path'] . '/' . $target);
    }
}
