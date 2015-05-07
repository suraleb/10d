<?php
/**
 * @author Kanstantsin A Kamkou
 * @link kkamkou at gmail dot com
 * @version $Id$
 */

class Cms_Feed_Reader extends Zend_Feed_Reader
{
    public static function import($uri, $etag = null, $lastModified = null)
    {
        if (!Cms_Config::getInstance()->cms->cache->disabled) {
            $cache = new Cms_Cache_File();
            $cache->setLifetime(86400);
            $cache->setOption('automatic_serialization', true);
            self::setCache($cache);
        }
        return parent::import($uri, $etag, $lastModified);
    }
}