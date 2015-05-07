<?php
/**
 * K System Engine
 *
 * @category Kkcms
 * @package  Cms
 * @author   Kanstantsin A Kamkou <kkamkou@gmail.com>
 * @license  http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons
 * @version  $Id$
 * @link     kkamkou@gmail.com
 */

/**
 * @package View
 * @subpackage Helper
 */
abstract class Cms_View_Helper
{
    /**
     * Instance of the view
     *
     * @var Zend_View
     */
    private $_view;

    /**
    * Save view locally
    *
    * @return void
    */
    public function setView(Zend_View_Interface $view)
    {
        $this->_view = $view;
    }

    /**
    * Get view saved locally
    *
    * @return Zend_View
    */
    public function getView()
    {
        return $this->_view->addScriptPath(CMS_ROOT . 'templates');
    }
}
