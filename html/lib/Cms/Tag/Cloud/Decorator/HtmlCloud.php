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

class Cms_Tag_Cloud_Decorator_HtmlCloud extends Zend_Tag_Cloud_Decorator_Cloud
{
    protected $_htmlTags = array(
        'ul' => array('id' => 'tags-cloud', 'class' => 'cleared')
    );

    protected $_separator = '';

    public function setHtmlTags(array $htmlTags)
    {
        $this->_htmlTags = $htmlTags;
        return $this;
    }

    public function getHtmlTags()
    {
        return $this->_htmlTags;
    }

    public function setSeparator($separator)
    {
        $this->_separator = $separator;
        return $this;
    }

    public function getSeparator()
    {
        return $this->_separator;
    }

    public function render(array $tags)
    {
        $cloudHtml = implode($this->getSeparator(), $tags);

        foreach ($this->getHtmlTags() as $key => $data) {
            if (is_array($data)) {
                $htmlTag    = $key;
                $attributes = '';

                foreach ($data as $param => $value) {
                    $attributes .= ' ' . $param . '="' . htmlspecialchars($value) . '"';
                }
            } else {
                $htmlTag    = $data;
                $attributes = '';
            }

            $cloudHtml = sprintf('<%1$s%3$s>%2$s</%1$s>', $htmlTag, $cloudHtml, $attributes);
        }

        return $cloudHtml;
    }
}
