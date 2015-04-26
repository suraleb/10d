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

/**
* @see Zend_View
*/
class Cms_Template extends Zend_View
{
    /**
     * Singleton variable
     *
     * @var Cms_EventManager
     */
    static protected $_instance = null;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $options = array(
            'encoding'   => 'UTF-8',
            'helperPath' => array('Cms_View_Helper_' => 'Cms/View/Helper'),
            'filterPath' => array('Cms_View_Filter_' => 'Cms/View/Filter'),
        );

        parent::__construct($options);

        $docType = new Zend_View_Helper_Doctype();
        $docType->setDoctype('XHTML1_TRANSITIONAL');
        unset($docType);

        // default variables
        $this->config = Cms_Config::getInstance();
        $this->input  = Cms_Input::getInstance();
        $this->lng    = Cms_Translate::getInstance();

        // compressor
        if (!CMS_DEBUG) {
            $this->addFilter('HtmlCompressor');
        }

        Zend_Paginator::setDefaultScrollingStyle('Sliding');
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('pager.phtml');
    }

    /**
     * Singleton
     *
     * @return Cms_Template
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}
