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
* @see Cms_Db_Table_Row_Abstract
*/
class Cms_Db_Table_Row_Static extends Cms_Db_Table_Row_Abstract
{
    /**
     * Increases views counter for this entry
     *
     * @param  int  $int (Default: null)
     * @return bool
     */
    public function incViewsCount($int = null)
    {
        $int = intval($int);

        $this->views += (empty($int) ? 1 : $int);

        return $this->save();
    }
}
