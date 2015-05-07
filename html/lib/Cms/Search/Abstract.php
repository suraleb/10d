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
 * @package  Search
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

abstract class Cms_Search_Abstract
{
    private $_db;

    private $_path;

    private $_fields = array();

    public function __construct($db)
    {
        // analyzer
        Zend_Search_Lucene_Analysis_Analyzer::setDefault(
            new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8_CaseInsensitive()
        );

        // query
        Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');

        // default search field
        Zend_Search_Lucene::setDefaultSearchField('text');

        // database
        $this->_dbOpen($db);
    }

    public function __destruct()
    {
        $this->_commit();
    }

    protected function _setHash(array $a)
    {
        foreach ($this->_map as $k => $v) {
            if (!array_key_exists($v[1], $a)) {
                throw new Cms_Exception(
                    "Column '{$v[1]}' doesn't exist in a database"
                );
            }

            if (in_array($v[0], array('Text', 'UnStored'))) {
                $txt = new Cms_Text_Parser($a[$v[1]]);
                $a[$v[1]] = Cms_Functions::strip_tags($txt->getText(false, false));
            }

            $this->_addField($v[0], $k, $a[$v[1]]);
        }
        return $this;
    }

    /**
     * Opening index if exists or creating new one
     * @param string $n
     */
    protected function _dbOpen($n)
    {
        $this->_path = CMS_TMP . "search/{$n}";
        if (!Cms_Filemanager::dirExists($this->_path)) {
            Zend_Search_Lucene::create($this->_path);
        }

        $this->_db = Zend_Search_Lucene::open($this->_path);
    }

    /**
     * Searching by mask and/or field
     *
     * @param string $query
     * @param string $field
     * @return Zend_Search_Lucene_Search_QueryHit
     */
    public function search($query, $field = null)
    {
        // search by field
        if (null !== $field) {
            $query = new Zend_Search_Lucene_Search_Query_Term(
                new Zend_Search_Lucene_Index_Term($query, $field)
            );
        }

        try {
            return $this->_db->find($query);
        } catch (Zend_Search_Lucene_Exception $exception) {
            return $exception;
        }
    }

    /**
     * Add field for the search document
     *
     * @param string $name
     * @param string $id
     * @param string $value
     */
    protected function _addField($name, $id, $value)
    {
        $set = array('Keyword', 'UnIndexed', 'Binary', 'Text' , 'UnStored');
        if (!in_array($name, $set)) {
            throw new Cms_Exception("Search field '{$name}' has incorrect name");
        }

        $this->_fields[] = array($name, $id, $value);
        return $this;
    }

    protected function _docAdd()
    {
        if (!count($this->_fields)) {
            return null;
        }

        $doc = new Zend_Search_Lucene_Document();
        foreach ($this->_fields as $v) {
            $doc->addField(
                Zend_Search_Lucene_Field::$v[0](
                    $v[1], $v[2], ($v[0] == 'Binary') ? null : 'utf-8'
                )
            );
        }

        $this->_db->addDocument($doc);
        $this->_fields = array();
        return $this;
    }

    /**
     * Removes document from index then inserts again
     * @param string $field field name
     * @param string $value search value
     */
    protected function _docUpdate($field, $value)
    {
        if (!count($this->_fields)) {
            return false;
        }
        $this->_docRemove($field, $value)->_docAdd();
        return $this;
    }

    /**
     * Removes document from index
     * @param string $field field name
     * @param string $value search value
     */
    protected function _docRemove($field, $value)
    {
        $entries = $this->search($value, $field);
        foreach ($entries as $entry) {
            $this->_db->delete($entry->id);
        }
        return $this;
    }

    public function optimize()
    {
        $this->_db->optimize();
        return $this;
    }

    protected function _commit()
    {
        $this->_db->commit();
        return $this;
    }
}
