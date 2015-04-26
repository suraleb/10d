<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Library
 * @package  Engine
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

/**
 * @see Cms_Db_Table_Row_Abstract
 */
class Cms_Db_Gallery_Row extends Cms_Db_Table_Row_Abstract
{
    public function getTags()
    {
        return $this->tags;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getAdded()
    {
        return $this->added;
    }
}
