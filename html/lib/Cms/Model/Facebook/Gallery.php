<?php

class Cms_Model_Facebook_Gallery extends Cms_Model_Abstract
{
    protected $_tableClass = 'Cms_Db_Facebook_Gallery';

    public function save($imageId, $fbImageId, $fbAlbumId, $fbPostId)
    {
        return $this->_db->save($imageId, $fbImageId, $fbAlbumId, $fbPostId);
    }

    public function fetchByImageId($imageId)
    {
        return $this->_db->find($imageId)->current();
    }

    public function fetchCountByPostId($postId)
    {
        return $this->_db->select()
                         ->from($this->_db->getTableName(), array('count(1) as cnt'))
                         ->where('fbPostId = ?', $postId)
                         ->query()
                         ->fetchColumn();
    }
}