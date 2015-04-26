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
 * {static tags:"title" order:"added-desc" count:10 from:0 tpl:"news-archive"
 *  rows:"static_id" exclude:"self OR rewrite" nocache}
 */
class Cms_Text_Parser_Static extends Cms_Text_Parser_Abstract
{
    protected static $_fields = array(
        'order'       => array('~^[a-z\s,_-]+$~', false),
        'count'       => array('~^\d+$~', false),
        'from'        => array('~^\d+$~', false),
        'tpl'         => array('~^[\w-]+$~', false),
        'rows'        => array('~^[a-z\s,_]+$~', false),
        'exclude'     => array('~^[\w\s,./-]+$~', false),

        'nopaginator' => array(null, false),
        'nocache'     => array(null, false),
    );

    public static function convert($query)
    {
        self::$_fields['tags'] = array(
            '~^[' . Cms_Tags::getRegexp(true) . ']+$~u', true
        );

        // Collect and validation of args
        $args = self::_parseQuery($query, self::$_fields);
        if (!$args) {
            return self::_badFormat();
        }

        $currentQueryHash = hash('crc32', $query);
        $input = Cms_Input::getInstance();
        $pageNum = intval($input->{"pager-{$currentQueryHash}"});

        // Cache check
        if (!isset($args['nocache']) && self::_isCachable() && $pageNum) {
            $cacheId = "parser_static_{$currentQueryHash}_{$pageNum}";

            $cache = new Cms_Cache_File();
            if (($output = $cache->load($cacheId))) {
                return $output;
            }
        }

        // Db object
        $db = new Cms_Db_Static();

        // Default select set
        $currentQuery = $db->select()
            ->where('active = ?', '1')->where('hidden = ?', '0');

        // Rows
        $currentQuery->from(
            $db, isset($args['rows']) ? self::_comma2list($args['rows']) :
            array('rewrite', 'title', 'user_id', 'user_name')
        );

        // Tags
        $subQuery = '';
        foreach (Cms_Tags::tokenize($args['tags']) as $key => $tags) {
            foreach ($tags as $tag) {
                switch ($key) {
                    case 'and':
                        $currentQuery->where('LOCATE(?, tags)', $tag);
                        break;
                    case 'not':
                        $currentQuery->where('!LOCATE(?, tags)', $tag);
                        break;
                    case 'or':
                        $subQuery .= $db->getAdapter()->quoteInto(
                            ' OR LOCATE(?, tags)', $tag
                        );
                        break;
                }
            }
        }

        if ($subQuery) {
            $currentQuery->where(ltrim($subQuery, ' OR'));
        }

        // Order
        if (isset($args['order'])) {
            self::_appendOrder(
                $currentQuery, $args['order'],
                array(
                    'added', 'updated', 'rewrite', 'title', 'order', 'featured',
                    'timestamp', 'id', 'views', 'user_id', 'user_name'
                )
            );
        } else {
            $currentQuery->order('order ASC');
        }

        // Exclude
        if (isset($args['exclude'])) {
            $exclude = self::_comma2list($args['exclude']);
            foreach ($exclude as $k=>&$v) {
                if ($v == 'self') {
                    $v = $input->rewrite;
                }
            }
            $currentQuery->where('rewrite NOT IN(?)', $exclude);
        }

        // Count and offset.
        $currentQuery->limit(
            !empty($args['count']) ? $args['count'] : null,
            !empty($args['from']) ? $args['from'] : null
        );

        // Pagination set
        $paginate = !empty($args['count']) && !isset($args['nopaginator']);

        // template engine and pager hash
        $tpl = Cms_Template::getInstance();
        $tpl->pagerHash = $currentQueryHash;

        // database execute and check
        try {
            $tpl->entries = (!$paginate) ? $db->fetchAll($currentQuery) :
                $db->getPaginatorRows($currentQuery, $pageNum, $args['count']);
        } catch (Zend_Db_Exception $e) {
            Cms_Core::log(
                "\nParser was called with the query: {$query}\nSQL: {$currentQuery}",
                Zend_Log::WARN
            );
            return self::_badFormat();
        }

        // template set and check
        if (empty($args['tpl'])) {
            $args['tpl'] = 'static-links';
        }

        $phtml = "parser/{$args['tpl']}.phtml";
        if (!Cms_Filemanager::fileExists(CMS_ROOT . "templates/frontend/scripts/{$phtml}", true)) {
            return self::_badFormat();
        }

        // output
        $output = $tpl->render($phtml);
        if (!isset($args['nocache']) && self::_isCachable() && $pageNum) {
            $cache->save($output, $cacheId, array('parser', 'static'));
        }
        return $output;
    }
}
