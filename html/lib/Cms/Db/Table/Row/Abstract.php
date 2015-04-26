<?php
/**
 * kkCms: Content Management System
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
* @see Zend_Db_Table_Row_Abstract
*/
class Cms_Db_Table_Row_Abstract extends Zend_Db_Table_Row_Abstract
{
    /**
     * Builds reference to custom db class
     *
     * @param string $tableName
     * @return Zend_Db_Table_Abstract
     */
    protected function _getTableFromString($tableName)
    {
        $cmsDbName = "Cms_Db_{$tableName}";
        if (class_exists($cmsDbName)) {
            $tableName = $cmsDbName;
        }
        return parent::_getTableFromString($tableName);
    }

    /**
     * Returns primary of the entry
     *
     * @return string
     */
    public function getId()
    {
        $primary = $this->getTable()->getPrimary();

        if (is_array($primary) && count($primary) > 1) {
            throw new Cms_Exception(
                'Primary is array. You should reload the "getId()" function.'
            );
        }
        return $this->{current($primary)};
    }
}
