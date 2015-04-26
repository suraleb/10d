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
 * @see Cms_Db_Gallery_Table
 */
class Cms_Db_Gallery extends Cms_Db_Gallery_Table
{
    const ORDER_ADDED = 'added';

    /**
     * @see Zend_Db_Table_Abstract
     * @var string
     */
    protected $_name = 'gallery';

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
     * @return int
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
        return parent::insert($data);
    }

    /**
     * Updates entry in the database
     *
     * @param  array $data
     * @param  string $where
     * @return int
     */
    public function update(array $data, $where)
    {
        if (!isset($data['updated'])) {
            $data['updated'] = time();
        }
        return parent::update($data, $where);
    }

    /**
     * Checks if entry already stored in the database
     *
     * @param  string $rewrite
     * @param  int    $id
     * @return bool
     */
    public function isUnique($rewrite, $id = null)
    {
        $args = array('rewrite = ?' => $rewrite);
        if (null !== $id) {
            array_push($args, "id != {$id}");
        }

        return (count($this->fetchRow($args)) > 0);
    }

    /**
     * Returns collections of the gallery
     *
     * @return Zend_Db_Table_Rowset
     */
    public function fetchCollections()
    {
        $row = $this->getAdapter()
            ->quoteInto("SUBSTRING(tags, 1, LOCATE(?, tags))", ']');

        return $this->fetchAll(
            $this->select()
                ->from($this, array('collection' => $row, 'tags'))
                ->group('collection')
                ->order('tags DESC')
        );

//        return $this->fetchAll(
//            $this->select()
//                 ->setIntegrityCheck(false)
//                 ->from(
//                    array('c' => $this->getTableName()),
//                    array('collection' => $row, 'c.tags')
//                 )
//                 ->joinLeft(array('g' => 'cms_fb_gallery'), 'g.imageId = c.id')
//                 ->joinLeft(array('a' => 'cms_fb_albums'), 'a.az = g.imageId')
//                 ->group('collection')
//                 ->order('c.tags DESC')
//        );

    }

    /**
     * Returns count of items in the collection
     *
     * @param  string $tag
     * @return int
     */
    public function getCollectionItemsCountByTag($tag)
    {
        return $this->getAdapter()->fetchOne(
            $this->select()->from($this, 'COUNT(id)')
                ->where('LOCATE(?, tags)', Cms_Tags::encode($tag))
        );
    }

    /**
     * Finds entries by hash
     *
     * @param  string $hash
     * @return Zend_Db_Table_Rowset
     */
    public function fetchByHash($hash)
    {
        return $this->fetchAll(array('hash = ?' => $hash));
    }

    /**
     * Finds count of entries by hash
     *
     * @param  string $hash
     * @return int
     */
    public function getCountByHash($hash)
    {
        return $this->getAdapter()->fetchOne(
            $this->select()
                ->from($this, 'COUNT(id)')
                ->where('hash = ?', $hash)
        );
    }

    /**
     * Finds entries by rewrite
     *
     * @param  string $rewrite
     * @return Zend_Db_Table_Rowset
     */
    public function fetchByRewrite($rewrite)
    {
        return $this->fetchAll(array('rewrite = ?' => $rewrite));
    }

    /**
     * Finds count of entries by rewrite
     *
     * @param  string $rewrite
     * @return int
     */
    public function getCountByRewrite($rewrite)
    {
        return $this->getAdapter()->fetchOne(
            $this->select()
                ->from($this, 'COUNT(id)')
                ->where('rewrite = ?', $rewrite)
        );
    }

    /**
     * Fetches last entry in the collection
     *
     * @return Zend_Db_Table_Rowset
     */
    public function fetchLastEntryByTag($tag)
    {
        return $this->fetchByTag(
            $tag, 1, null, self::ORDER_ADDED, Zend_Db_Select::SQL_DESC
        )->current();
    }
}
