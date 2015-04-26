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

class Cms_User_Export_Db implements Cms_User_Export_Interface
{
    public static function save(array $hash)
    {
        $db = new Cms_Db_Users();
        foreach ($hash as $key => $value) {
            $db->{$key} = $value;
        }

        return $db->save();
    }
}
