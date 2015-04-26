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
 * @see Cms_User_Import_Interface
 */
class Cms_User_Recognize_Numeric extends Cms_User_Recognize implements Cms_User_Recognize_Interface
{
    /**
     * Retrieves a user from the database (uses numeric id)
     *
     * @param  int $id
     * @return Cms_Db_Users_Row|false
     */
    public function retrieve($id)
    {
        return $this->getById(intval($id));
    }
}
