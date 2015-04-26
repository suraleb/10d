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
 * @see Cms_User_Recognize_Interface
 */
class Cms_User_Recognize extends Cms_Db_Users
{
    /**
     * Retrieves a user from the database according object or a numeric value
     *
     * @param  mixed $data
     * @return Cms_Db_Users_Row|false
     */
    public static function factory($data)
    {
        $class = 'Cms_User_Recognize_';
        switch (true) {
            case is_numeric($data):
                $class .= 'Numeric';
                break;
            case ($data instanceof Zend_Auth):
                $class .= 'Auth';
                break;
            default:
                throw new Cms_User_Exception("Unknown user type for import");
                break;
        }

        $actor = call_user_func(array(new $class, 'retrieve'), $data);

        return ($actor instanceof Cms_Db_Users_Row) ? $actor : self::factory(0);
    }
}
