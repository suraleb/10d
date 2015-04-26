<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Library
 * @package  Engine
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

/**
 *@see Cms_Model_Abstract
 */
class Cms_Model_Ecommerce_Entry extends Cms_Model_Abstract
{
    protected $_tableClass = 'Cms_Db_Module_Ecommerce_Entries';

    protected $_mediaStorage = array();

    const ENTRY_MEDIA_TYPE_IMAGE = 'image';
    const ENTRY_MEDIA_TYPE_MOVIE = 'movie';

    public function __construct()
    {
        parent::__construct();

        $this->setInStock(true)
            ->setTimeAdded(time());
    }

    public function setTitle($string)
    {
        if (Cms_Functions::strlen($string) < 2
            || Cms_Functions::strlen($string) > 100) {
            return $this->addMessage('MSG_ADMIN_ECOMMERCE_PRODUCT_WRONG_LEN');
        }

        // we should remove quotes from the title
        $string = str_replace(array('"', "'"), '', $string);

        $this->title = $string;
        return $this;
    }

    public function setDescription($string)
    {
        if (Cms_Functions::strlen($string) < 10) {
            return $this->addMessage('MSG_ADMIN_ECOMMERCE_PRODUCT_WRONG_DESCR');
        }
        $this->description = $string;
        return $this;
    }

    public function setInStock($bool)
    {
        $this->inStock = (bool)$bool ? '1' : '0'; // enum here
        return $this;
    }

    public function setTimeAdded($time)
    {
        $this->timeAdded = $time;
        return $this;
    }

    public function setCover($path)
    {
        $this->cover = $path;
        return $this;
    }

    public function setMediaContent(array $content = array())
    {
        $this->mediaContent = serialize($content);
        return $this;
    }

    public function addMedia($value, $type)
    {
        $this->_mediaStorage[$type][] = $value;
        return $this;
    }

    public function _saveBefore()
    {
        $this->setMediaContent($this->_mediaStorage);
    }
}
