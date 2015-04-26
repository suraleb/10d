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

class Cms_Model_Gallery extends Cms_Model_Abstract
{
    const TYPE_IMAGE   = 'image';
    const TYPE_FILE    = 'file';
    const TYPE_MOVIE   = 'movie';
    const TYPE_ARCHIVE = 'archive';

    protected $_tableClass = 'Cms_Db_Gallery';

    /**
     * ID of the curent user
     *
     * @param  int $id
     * @return Cms_Model_Gallery
     */
    public function setId($id)
    {
        if (!is_numeric($id) || $id < 0) {
            throw new Cms_Model_Exception('Id must be integer and positive');
        }
        $this->id = $id;
        return $this;
    }

    /**
     * Description of the entry
     *
     * @param  string $descr
     * @return Cms_Model_Gallery
     */
    public function setDescr($descr)
    {
        $this->descr = $descr;
        return $this;
    }

    /**
     * Options for the entry
     *
     * @param  array $options
     * @return Cms_Model_Gallery
     */
    public function setOptions(array $options)
    {
        $this->options = serialize($options);
        return $this;
    }

    public function setUserId($uid)
    {
        $this->user_id = $uid;
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setRewrite($rewrite)
    {
        $this->rewrite = $rewrite;

        if ($this->_db->getCountByRewrite($rewrite)) {
            $this->addMessage('MSG_ADMIN_GALLERY_FILE_EXISTS');
        }

        return $this;
    }

    public function setPath($path)
    {
        $this->path = $path;

        // extension set
        if (empty($this->_fields['ext'])) {
            $this->ext = pathinfo($path, PATHINFO_EXTENSION);
        }

        return $this;
    }

    public function setTags($tags)
    {
        $this->tags = Cms_Tags::encode($tags);
        return $this;
    }

    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    public function setExt($ext)
    {
        $this->ext = $ext;
        return $this;
    }

    public function setMime($mime = 'application/octet-stream')
    {
        $this->mime = $mime;
        return $this;
    }

    public function setAdded($timestamp)
    {
        $this->added = $timestamp;
        return $this;
    }

    public function setHash($hash)
    {
        $this->hash = $hash;

        if ($this->_db->getCountByHash($hash)) {
            $this->addMessage('MSG_ADMIN_GALLERY_HASH_EXISTS');
        }

        return $this;
    }
}
