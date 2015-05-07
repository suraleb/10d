<?php

class Cms_Model_Facebook_Albums extends Cms_Model_Abstract
{
     protected $_tableClass = 'Cms_Db_Facebook_Albums';

    public function save($fbAlbumId, $tag)
    {
        $row = $this->_db->select()->where('fbAlbumId = ?', $fbAlbumId)->where('tag = ?', $tag)->query()->fetchAll();

        if (!empty($row)) {
            return false;
        }

        $row = $this->_db->createRow();
        $row->fbAlbumId = $fbAlbumId;
        $row->tag       = $tag;
        return (bool) $row->save();
    }

    public function fetchIdByTag($tag)
    {
        return $this->_db->select()
                         ->from($this->_db->getTableName(), array('fbAlbumId'))
                         ->where('tag = ?', $tag)
                         ->query()
                         ->fetchColumn();
    }
}
