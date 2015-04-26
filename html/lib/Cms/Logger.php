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
 * Saves messages with actions done
 */
class Cms_Logger
{
    /**
     * Custom user ID
     *
     * @var mixed
     */
    private static $_userId;

    /**
     * Reset custom user ID
     *
     * @param  int $id
     * @return void
     */
    public static function setCustomUserId($id)
    {
        $id = intval($id);
        if (!empty($id)) {
            self::$_userId = $id;
        }
    }

    /**
     * Makes database entry
     *
     * @param  mixed $message
     * @param  string $module
     * @param  string $action
     * @return bool
     */
    public static function log($message, $module, $action)
    {
        // action strip
        $action = str_replace('Action', '', $action);

        // message cleanup
        if (is_array($message)) {
            array_walk_recursive(
                $message,
                create_function('&$s', '$s = str_replace("|", "", $s);')
            );
            $message = implode('|', $message);
        }

        // ip
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';

        // user id
        $userId = 0;
        if (is_integer(self::$_userId)) {
            $userId = self::$_userId;
        } else {
            if (!CMS_CLI) {
                $userId = Zend_Registry::get('actor')->getId();
            }
        }

        // database insert
        $db = new Cms_Db_Logs();
        return $db->insert(
            array(
                'text'    => $message,
                'user_id' => $userId,
                'action'  => $action,
                'module'  => $module,
                'ip'      => new Zend_Db_Expr("INET_ATON('{$ip}')")
            )
        );
    }
}
