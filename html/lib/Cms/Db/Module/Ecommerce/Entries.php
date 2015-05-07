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
 * @see Cms_Db_Module_Ecommerce_Entries_Table
 */
class Cms_Db_Module_Ecommerce_Entries extends Cms_Db_Module_Ecommerce_Entries_Table
{
    /**
     * @see Zend_Db_Table_Abstract
     * @var string
     */
    protected $_name = 'module_ecommerce_entries';

    /**
     * @see Zend_Db_Table_Abstract
     * @var string
     */
    protected $_primary = 'id';

    /**
     * Returns query for the admin-index page
     *
     * @return Zend_Db_Select
     */
    public function getQueryAdminList()
    {
        return $this->select(true)
            ->order('timeAdded DESC');
    }
}
