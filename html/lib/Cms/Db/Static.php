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
 * @see Cms_Db_Static_Table
 */
class Cms_Db_Static extends Cms_Db_Static_Table
{
    /**
     * @see Zend_Db_Table_Abstract
     * @var string
     */
    protected $_name = 'static';

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
            'refColumns'     => 'id'
        )
    );

    /**
     * @see Zend_Db_Table_Abstract
     * @var bool
     */
    protected $_sequence = false;

    /**
     * Inserts hash into database
     *
     * @param  array $data
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
            $subQuery = $this->select()->from($this, new Zend_Db_Expr('MAX(`order`)'));
            if (!empty($data['parents'])) {
                $subQuery->where('LOCATE(?, tags)', $data['parents']);
            }

            $data['order'] = $this->getAdapter()->fetchOne($subQuery);
            $data['order'] += 1;
        }

        return parent::insert($data);
    }

    /**
     * Finds parents by rewrite
     *
     * @param  mixed $parents
     * @param  int   $active (Default: 1)
     * @param  int   $hidden (Default: 0)
     * @return Zend_Db_Table_Rowset
     */
    public function fetchParents($parents, $active = 1, $hidden = 0)
    {
        if (is_array($parents)) {
            $parents = Cms_Tags::decode($parents);
        }

        $parents = Cms_Tags::encode($parents);

        $order = $this->getAdapter()->quoteInto('FIELD(rewrite, ?)', $parents);

        return $this->fetchAll(
            $this->select()->from($this, array('title', 'rewrite'))
                ->where('active = ?', (string)$active) // enum here
                ->where('hidden = ?', (string)$hidden) // enum here
                ->where('rewrite IN(?)', $parents)
                ->order($order)
        );
    }
}
