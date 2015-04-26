<?php

class Cms_Db_Facebook_Gallery extends Cms_Db_Table_Abstract
{
    protected $_name    = 'fb_gallery';
    protected $_primary = 'imageId';

    public function save($imageId, $fbImageId, $fbAlbumId, $fbPostId)
    {
        try {
            $row = $this->select()
                        ->from($this->_name, 'imageId')
                        ->where('imageId = ?', $imageId)
                        ->where('fbImageId = ?', $fbImageId)
                        ->where('fbAlbumId = ?', $fbAlbumId)
                        ->where('fbPostId = ?', $fbPostId)
                        ->query()
                        ->fetchColumn();

            if (!$row) {
                $row = $this->createRow(
                    array(
                        'imageId'     => $imageId,
                        'fbImageId'   => $fbImageId,
                        'fbAlbumId'   => $fbAlbumId,
                        'fbPostId'    => $fbPostId,
                    )
                );
                return $row->save();
            }
        } catch (Exception $e) {
            error_log($e->getMessage() . $e->getTraceAsString());
            return false;
        }
    }
}