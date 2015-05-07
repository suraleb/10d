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
 * @version  $Id$
 */

/**
 * @see Zend_Db_Table_Abstract
 */
abstract class Cms_Db_Table_Abstract extends Zend_Db_Table_Abstract
{
    /**
     * Sets default row-class of the table
     * @var string
     */
    protected $_rowClass = 'Cms_Db_Table_Row_Abstract';

    /**
     * Constructor
     *
     * @param  array $config
     * @return void
     */
    public function __construct($config = array())
    {
        parent::setDefaultAdapter(Cms_Db::getInstance());

        parent::__construct($config);

        if (!Cms_Config::getInstance()->cms->cache->disabled) {
            $cache = new Cms_Cache_File();
            $cache->setOption('automatic_serialization', true);
            $this->setDefaultMetadataCache($cache);
        }
    }

    /**
     * Returns Primary Key
     *
     * @return string
     */
    public function getPrimary()
    {
        return $this->_primary;
    }

    /**
     * Returns name of the table
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->info(self::NAME);
    }

    /**
     * get row by id
     *
     * @param  integer $id  primary key
     * @return mixed
     */
    public function getById($id)
    {
        $rowset = $this->find($id);
        return count($rowset) ? $rowset->current() : false;
    }

    /**
     * Removes item by id
     *
     * @param  integer $id primary key
     * @return integer The number of rows deleted
     */
    public function deleteById($id)
    {
        if (is_array($this->_primary)) {
            $this->_primary = current($this->_primary);
        }
        return $this->delete($this->_primary . ' = ' . intval($id));
    }

    /**
     * Gets max id from database
     *
     * @return integer
     */
    public function getMaxId()
    {
        if (!in_array('id', $this->info(Zend_Db_Table_Abstract::COLS))) {
            throw new Cms_Exception("Table '{$this->_name}' has no `id` column");
        }

        return $this->getAdapter()->fetchOne(
            $this->select()->from($this, 'MAX(id)')
        );
    }

    /**
     * Unexistent methods handler
     *
     * @param string $name
     * @param mixed  $arguments
     */
    public function __call($name, $arguments)
    {
        //handles get by dynamic finder like getByNameAndPasswordOrDate()
        if (strpos($name, 'getBy') === 0) {
            return $this->_getByColumnsFinder(
                str_replace('getBy', '', $name), $arguments
            );
        }
        return false;
    }

    /**
     * Creates paginator for a query
     *
     * @param mixed $select
     * @param int   $page (Default: 1)
     * @param int   $items (Default: 10)
     * @param int   $pageRange (Default: 7)
     * @return Zend_Paginator
     */
    public function getPaginatorRows($select, $page = 1, $items = 10, $pageRange = 7)
    {
        $paginator = Zend_Paginator::factory($select);
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage($items)
            ->setPageRange($pageRange);

        if (!Cms_Config::getInstance()->cms->cache->disabled) {
            $cache = new Cms_Cache_File();
            $cache->setOption('automatic_serialization', true);

            $paginator->setCache($cache);
        }

        return $paginator;
    }

    /**
     * Returns entries maching the tag
     *
     * @return Zend_Db_Table_Rowset
     */
    public function fetchAllByTag($tag)
    {
        return $this->fetchByTag($tag);
    }

    /**
     * Returns entries maching the tag
     *
     * @param  int $limit (Default: null)
     * @param  int $offset (Default: null)
     * @return Zend_Db_Table_Rowset
     */
    public function fetchByTag(
        $tag, $limit = null, $offset = null, $order = null,
        $sort = Zend_Db_Select::SQL_ASC
    )
    {
        $query = $this->select(true)
            ->where('LOCATE(?, tags)', Cms_Tags::encode($tag));

        if (null !== $limit) {
            $query->limit($limit, $offset);
        }

        if (null !== $order) {
            $query->order($order . ' ' . $sort);
        }

        return $this->fetchAll($query);
    }

    /**
     * Adds prefix for the table name
     *
     * @return void
     */
    protected function _setupTableName()
    {
        parent::_setupTableName();

        $this->_name = Cms_Config::getInstance()->database
            ->prefix . '_' . $this->_name;
    }

    /**
     * @param  string $query
     * @param  array  $values
     * @return null|Model_User
     */
    private function _getByColumnsFinder($query, $values)
    {
        $params = $this->_parseQuery($query);
        if (!$params) {
            return false;
        }
        return $this->fetchRow($this->_buildSelect($params, $values));
    }

    /**
     * Parse query to array
     *
     * @param string $query
     * @return array
     */
    private function _parseQuery($query)
    {
        $matches = array();
        if (preg_match_all('~[A-Z][^A-Z]+~', $query, $matches)) {
            return array_map('strtolower', $matches['0']);
        }
        return false;
    }

    /**
     * Builds Zend_Db_Table_Select object
     *
     * @param  array $params
     * @param  array $values
     * @return object Zend_Db_Table_Select
     */
    private function _buildSelect($params, $values)
    {
        $select = $this->select();

        $fields = $this->info(Zend_Db_Table_Abstract::COLS);
        $fields = array_map('strtolower', $fields);

        $condition = '';
        foreach ($params as $param) {
            if (in_array($param, $fields)) {
                $value = array_shift($values);
                if ($value === null) {
                    throw new Cms_Exception("No value for the '{$param}' field");
                }

                if ($value instanceof Zend_Db_Expr) {
                    $value = $value->__toString();
                }

                if ($condition == 'or') {
                    $select->orWhere("{$param} = ?", $value);
                } else {
                    $select->where("{$param} = ?", $value);
                }

                continue;
            }

            if (in_array($param, array('or', 'and'))) {
                $condition = $param;
                continue;
            }

            throw new Cms_Exception(
                "No such condition must be OR or AND, got: {$param}"
            );
        }

        return $select;
    }
}
