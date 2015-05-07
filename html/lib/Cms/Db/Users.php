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
 * @see Cms_Db_Users_Table
 */
class Cms_Db_Users extends Cms_Db_Users_Table
{
    const AUTH_CREDENTIAL_TREATMENT = 'MD5(CONCAT(MD5(?), password_salt))';
    const AUTH_CREDENTIAL_COLUMN    = 'password';
    const AUTH_IDENTITY_COLUMN      = 'mail';

    /**
     * @see Zend_Db_Table_Abstract
     * @var string
     */
    protected $_name = 'users';

    /**
     * @see Zend_Db_Table_Abstract
     * @var string
     */
    protected $_primary = 'id';

    /**
     * @see Zend_Db_Table_Abstract
     * @var bool
     */
    protected $_sequence = false;

    /**
     * @see Zend_Db_Table_Abstract
     * @var array
     */
    protected $_dependentTables = array('Gallery', 'Polls', 'Static', 'Logs');

    /**
     * @see Zend_Db_Table_Abstract
     * @var array
     */
    protected $_referenceMap    = array(
        'Static' => array(
            'columns'           => array('id'),
            'refTableClass'     => 'Cms_Db_Static',
            'refColumns'        => array('user_id')
        )
    );

    /**
     * Inserts hash into database
     *
     * @param  array $data
     * @return bool
     */
    public function insert(array $data)
    {
        if (!isset($data['id'])) {
            $data['id'] = $this->getMaxId() + 1;
        }
        return parent::insert($data);
    }
}
