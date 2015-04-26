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
 * {module name:"module-name" action:"action" params:"param=value,hello=world"}
 */
class Cms_Text_Parser_Module extends Cms_Text_Parser_Abstract
{
    protected static $_fields = array(
        'name'       => array('~^[\w-]+$~', true),
        'action'     => array('~^\w+$~', false),
        'params'     => array('~^[^"]+$~', false)
    );

    public static function convert($query)
    {
        // collect and validation of args
        $args = self::_parseQuery($query, self::$_fields);
        if (!$args) {
            return self::_badFormat();
        }

        $parts = explode('-', $args['name']);
        $parts = array_map('strtolower', $parts);
        $parts = array_map('ucfirst', $parts);

        // building script address and module check
        $path = CMS_ROOT . 'actions' . CMS_SEP;
        if (isset($parts[1])) {
            $call = $parts[0] . '_c' . $parts[1];
            $path .= $parts[0] . CMS_SEP . 'c' . $parts[1];
        } else {
            $call  = 'c' . $parts[0];
            $path .= $call;
        }
        $path .= '.php';

        if (Cms_Filemanager::fileExists($path, true)) {
            require_once $path;
        }

        if (!class_exists($call, false)) {
            return null;
        }

        $action = 'run';
        if (isset($args['action'])) {
            $action = strtolower($args['action']) . 'Action';
        }

        // apply params
        if (isset($args['params'])) {
            $input = Cms_Input::getInstance();
            foreach (explode(',', $args['params']) as $v) {
                if (!Cms_Functions::strpos($v, '=')) {
                    continue;
                }

                $v = explode('=', $v);

                if ($v[1] != 'self') {
                    $input->{trim($v[0])} = trim($v[1]);
                    continue;
                }

                if (!isset($input->{trim($v[0])})) {
                    return self::_badFormat();
                }
            }
        }

        $obj = new $call(false);

        if (!method_exists($obj, $action)) {
            return null;
        }

        return call_user_func(array(&$obj, $action));
    }
}
