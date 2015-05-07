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
class Cms_Db_Logs extends Cms_Db_Table_Abstract
{
    /**
     * @see Zend_Db_Table_Abstract
     * @var string
     */
    protected $_name = 'logs';

    /**
     * @see Zend_Db_Table_Abstract
     * @var string
     */
    protected $_primary = 'id';

    /**
     * @see Zend_Db_Table_Abstract
     * @var array
     */
    protected $_referenceMap = array(
        'Users' => array(
            'columns'        => 'user_id',
            'refTableClass'  => 'Cms_Db_Users',
            'refColumns'     => 'id',
            'onDelete'       => self::CASCADE
        )
    );

    /**
     * Inserts hash into database
     *
     * @see Cms_Db_Table_Abstract
     * @param array $data
     * @return bool
     */
    public function insert(array $data)
    {
        if (!isset($data['time'])) {
            $data['time'] = time();
        }

        if (!isset($data['id'])) {
            $data['id'] = $this->getMaxId();
            $data['id'] += 1;
        }

        return parent::insert($data);
    }
}
