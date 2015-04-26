<?php
/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Text
 * @package  Parser
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

/**
 * Example:
 * {gallery items:"test,test3" tags:"test,testz" count:10 from:0
 *  order:"added,size" tpl:"tpl" nocache}
 */
class Cms_Text_Parser_Gallery extends Cms_Text_Parser_Abstract
{
    protected static $_fields = array(
        'items'   => array('~^[\p{L}\p{N}\p{Z}_,-]+$~u', false),
        'count'   => array('~^\d+$~', false),
        'from'    => array('~^\d+$~', false),
        'order'   => array('~^[a-z\s,-]+$~', false),
        'tpl'     => array('~^[\w-]+$~', false),
        'nocache' => array(null, false),
    );

    public static function convert($query)
    {
        self::$_fields['tags'] = array(
            '~^[' . Cms_Tags::getRegexp(true) . ']+$~u', false
        );

        // collect and validation of args
        $args = self::_parseQuery($query, self::$_fields);
        if (!$args) {
            return self::_badFormat();
        }

        // default check. we need items or tags to be here
        if (!isset($args['items']) && !isset($args['tags'])) {
            return self::_badFormat();
        }

        // current group id
        static $groupId = 1;

        // cache check
        if (self::_isCachable() && !isset($args['nocache'])) {
            $cacheId = "parser_gallery_{$groupId}_" . hash('crc32', $query);

            $cache = new Cms_Cache_File();
            if (($output = $cache->load($cacheId))) {
                return $output;
            }
        }

        $db = new Cms_Db_Gallery();

        // items select
        if (isset($args['items'])) {
            $dbQueryItems = $db->select()->where(
                'rewrite IN (?)', self::_comma2list($args['items'])
            );
        }

        // append tags
        if (isset($args['tags'])) {
            $dbQuery = $db->select();

            $subTagsQuery = '';
            foreach (Cms_Tags::tokenize($args['tags']) as $key => $tags) {
                foreach ($tags as $tag) {
                    switch ($key) {
                        case 'and':
                            $dbQuery->where('LOCATE(?, tags)', $tag);
                            break;
                        case 'not':
                            $dbQuery->where('!LOCATE(?, tags)', $tag);
                            break;
                        case 'or':
                            $subTagsQuery .= $db->getAdapter()->quoteInto(
                                ' OR LOCATE(?, tags)', $tag
                            );
                            break;
                    }
                }
            }

            if ($subTagsQuery) {
                $dbQuery->where(ltrim($subTagsQuery, ' OR'));
            }
        }

        // selects merge
        if (isset($args['items']) && isset($args['tags'])) {
            $dbQuery->orWhere(implode(' ', $dbQueryItems->getPart('where')));
        } else {
            $dbQuery = isset($dbQuery) ? $dbQuery : $dbQueryItems;
        }

        // append order
        if (isset($args['order'])) {
            self::_appendOrder(
                $dbQuery, $args['order'], array('added', 'updated', 'size', 'name', 'id')
            );
        }

        // count and offset
        if (isset($args['count'])) {
            $dbQuery->limit($args['count'], !empty($args['from']) ? $args['from'] : null);
        }

        try {
            $entries = $db->fetchAll($dbQuery);
        } catch (Zend_Db_Exception $e) {
            return self::_badFormat();
        }

        if (!count($entries)) {
            return null;
        }

        $items = array();
        foreach ($entries->toArray() as $e) {
            if (!empty($e['options'])) {
                $e['options'] = unserialize($e['options']);
            }
            $items[$e['type']][] = $e;
        }

        $tpl = Cms_Template::getInstance();
        $tpl->group = $groupId++;

        // template
        if (!isset($args['tpl'])) {
            $args['tpl'] = 'gallery';
        }

        $out = '';
        foreach (array_keys($items) as $k) {
            $tpl->entries = $items[$k];
            try {
                $out .= $tpl->render("parser/{$args['tpl']}-{$k}.phtml");
            } catch (Zend_View_Exception $e) {
                $out .= "Unable to parse the '{$args['tpl']}-{$k}' template";
            }
        }

        // cache save
        if (self::_isCachable() && !isset($args['nocache'])) {
            $cache->save($out, $cacheId, array('parser', 'gallery'));
        }

        return $out;
    }
}
