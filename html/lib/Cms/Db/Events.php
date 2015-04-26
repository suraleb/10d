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
 * @see Cms_Db_Table_Abstract
 */
class Cms_Db_Events extends Cms_Db_Table_Abstract
{
    /**
     * @see Zend_Db_Table_Abstract
     * @var string
     */
    protected $_name = 'module_events';

    /**
     * @see Zend_Db_Table_Abstract
     * @var string
     */
    protected $_primary = 'id';

    /**
     * Inserts hash into database
     *
     * @see Cms_Db_Table_Abstract
     * @param array $data
     * @return bool
     */
    public function insert(array $data)
    {
        if (!isset($data['added'])) {
            $data['added'] = time();
        }

        if (!isset($data['id'])) {
            $data['id'] = $this->getMaxId();
            $data['id'] += 1;
        }

        if (!isset($data['order'])) {
            $data['order'] = $this->getAdapter()->fetchOne(
                $this->select()->from($this, new Zend_Db_Expr('MAX(`order`)'))
            );
            $data['order'] += 1;
        }

        return parent::insert($data);
    }

    /**
     * Updates entry in the database
     *
     * @see Cms_Db_Table_Abstract
     * @param array $data
     * @param string $where
     * @return bool
     */
    public function update(array $data, $where)
    {
        if (!isset($data['updated'])) {
            $data['updated'] = time();
        }
        return parent::update($data, $where);
    }
}
