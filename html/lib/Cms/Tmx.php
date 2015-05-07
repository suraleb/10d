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

class Cms_Tmx
{
    protected $_list;

    protected $_parser;

    protected $_lng;

    protected $_data;

    protected $_key;

    protected $_isSeg = false;

    public function __construct()
    {
        $this->_list = array();

        $this->_parser = xml_parser_create();

        xml_set_object($this->_parser, $this);

        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 0);

        xml_set_element_handler($this->_parser, 'elementStart', 'elementEnd');

        xml_set_character_data_handler($this->_parser, 'dataContent');
    }

    public function setData(&$c)
    {
        return xml_parse($this->_parser, $c);
    }

    public function getError()
    {
        return xml_error_string(xml_get_error_code($this->_parser));
    }

    public function getList()
    {
        return $this->_list;
    }

    protected function elementStart($p, $n, $attr)
    {
        switch (strtolower($n)) {
            case 'tu': {
                if (isset($attr['tuid'])) {
                    $this->_key = $attr['tuid'];
                }
            } break;

            case 'tuv': {
                if (isset($attr['xml:lang'])) {
                    $this->_lng = $attr['xml:lang'];
                }
            } break;

            case 'seg': {
                $this->_isSeg = true;
                $this->_data = '';
            } break;
        }
    }

    protected function elementEnd($p, $n)
    {
        switch (strtolower($n)) {
            case 'tu': {
                $this->_key = '';
            } break;

            case 'tuv': {
                $this->_lng = '';
            } break;

            case 'seg': {
                $this->_isSeg = false;

                if (!empty($this->_data) || !isset($this->_list[$this->_key])) {
                    $this->_list[$this->_lng][$this->_key] = $this->_data;
                }
            } break;
        }
    }

    protected function dataContent($p, $d)
    {
        if ($this->_isSeg && (strlen($this->_key) > 0) && (strlen($this->_lng) > 0)) {
            $this->_data = $d;
        }
    }
}
