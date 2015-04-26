<?php
/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Text
 * @package  Parser
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

/**
 * Works with text
 */
class Cms_Text_Parser
{
    /**
     * Regexp string for pagebreak
     * @var string
     */
    const PAGE_BREAK_MASK = '~(<p([^>]+)?>)?(\s|&nbsp;)*<!-- pagebreak -->(\s|&nbsp;)*(</p>)?~i';

    /**
     * Count of pagebreaks
     * @var int
     */
    protected $_partsCount = 0;

    /**
     * Current position
     * @var int
     */
    protected $_partCurrent = 1;

    /**
     * text for conversion
     * @var string
     */
    protected $_text;

    /**
     * Advanced options
     * @var array
     */
    protected $_options = array();

    /**
     * Default parsers
     * @var array
     */
    protected $_parsers = array(
        'gallery', 'module', 'poll', 'static'
    );

    /**
     * Parsers were used with this text
     * @var array
     */
    protected $_parsersUsed = array();

    /**
     * constructor
     *
     * @param string $txt
     * @return Cms_Text_Parser
     */
    public function __construct($txt = null)
    {
        if ($txt !== null) {
            $this->setText($txt);
        }
    }

    /**
     * Sets option for the parser
     *
     * @param string $n
     * @param mixed $v
     * @return Cms_Text_Parser
     */
    public function setOption($n, $v)
    {
        if (!array_key_exists($n, $this->_options)) {
            return false;
        }
        $this->_options[$n] = $v;
        return $this;
    }

    /**
     * Returns converted text
     *  $format - means to pase queries
     *  $pagebreak - means to fetch according "pagebreak"
     *
     * @param bool $convert (Default: true)
     * @param bool $pagebreak (Default: true)
     */
    public function getText($convert = true, $pagebreak = true)
    {
        if ($convert) {
            Cms_Text_Parser_Custom::convertPre($this->_text);
            $this->_convert();
            Cms_Text_Parser_Custom::convertPost($this->_text);
        }

        if ($this->getPartsCount() > 1) {
            if ($pagebreak) {
                $parts = preg_split(self::PAGE_BREAK_MASK, $this->_text);
                if ($this->_partCurrent == 2) {
                    return implode('', array_slice($parts, 0, 2));
                }
                return $parts[$this->getPartNumber() - 1];
            } else {
                $this->_partsCount = 0;
            }
        }

        $this->_cleanup();

        return $this->_text;
    }

    /**
     * Sets text to parser
     *
     * @param string $text
     * @return Cms_Text_Parser
     */
    public function setText($text)
    {
        $this->_text = $text;

        // calculating pages
        $matches = array();
        preg_match_all(
            self::PAGE_BREAK_MASK, $this->_text, $matches, PREG_SET_ORDER
        );

        $this->_partsCount = count($matches) + 1;

        return $this;
    }

    /**
     * Returns list of parsers were used for text
     *
     * @return array
     */
    public function getParsersUsed()
    {
        return $this->_parsersUsed;
    }

    /**
     * Returns count of pages according "pagebreak"
     *
     * @return int
     * @see getText()
     */
    public function getPartsCount()
    {
        return $this->_partsCount;
    }

    /**
     * Returns current page number according "pagebreak"
     *
     * @return int
     * @see getText()
     */
    public function getPartNumber()
    {
        return $this->_partCurrent;
    }

    /**
     * Sets page number according "pagebreak"
     *
     * @param int $num
     * @return Cms_Text_Parser
     */
    public function setPartNumber($num)
    {
        if ($num > $this->getPartsCount() || $num <= 0) {
            $num = 1;
        }
        $this->_partCurrent = intval($num);
        return $this;
    }

    /**
     * Converts text. Replaces system queries.
     *
     * @see getText()
     * @return void
     */
    protected function _convert()
    {
        $regexList = implode('|', $this->_parsers);

        // moving from <p>{query}</p> to {query}
        $this->_text = preg_replace(
            "~<p>\s*\{({$regexList})([^}]+)\}\s*</p>~i", '{\1\2}', $this->_text
        );

        $matches = array();
        if (!preg_match_all("~\{({$regexList})([^}]+)\}~i", $this->_text, $matches, PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $qPart) {
            $qPart[1] = strtolower($qPart[1]);

            // text convertation
            $this->_text = str_replace(
                $qPart[0], call_user_func(
                    array('Cms_Text_Parser_' . ucfirst($qPart[1]), 'convert'), $qPart[2]
                ),
                $this->_text
            );

            // we are using this parser, so lets say this
            $this->_parsersUsed[] = $qPart[1];
        }
    }

    /**
     * Removes special data from a text
     *
     * @return void
     */
    protected function _cleanup()
    {
        $this->_text = preg_replace(
            '~\{(' . implode('|', $this->_parsers) . ').+\}~', '', $this->_text
        );
    }
}
