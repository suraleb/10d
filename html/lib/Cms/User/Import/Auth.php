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

class Cms_User_Import_Auth implements Cms_User_Import_Interface
{
    public function retrieve($data)
    {
        if (!$data->hasIdentity()) {
            return false;
        }

        $db = new Cms_Db_Users();

        $dbUser = $db->getByMail($data->getIdentity());
        if (!$dbUser) {
            return false;
        }

        $user = new Cms_User();
        return $user->import($dbUser);
    }
}
