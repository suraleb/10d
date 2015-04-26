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


class Cms_Search_Static extends Cms_Search_Abstract
    implements Cms_Search_Interface
{
    protected $_map = array(
        'rewrite'   => array('UnIndexed', 'rewrite'),
        'added'     => array('UnIndexed', 'added'),
        'updated'   => array('UnIndexed', 'updated'),
        'timestamp' => array('UnIndexed', 'timestamp'),
        'tags'      => array('UnIndexed', 'tags'),
        'text'      => array('UnStored', 'content'),
        'sid'       => array('Keyword', 'id'),
        'title'     => array('Text', 'title')
    );

    public function __construct()
    {
        parent::__construct('static');
    }

    public function setHash(array $data)
    {
        return parent::_setHash($data);
    }

    public function docAdd()
    {
        return $this->_docAdd();
    }

    public function docUpdate($id)
    {
        return $this->_docUpdate('sid', intval($id));
    }

    public function docRemove($id)
    {
        return $this->_docRemove('sid', intval($id));
    }
}
