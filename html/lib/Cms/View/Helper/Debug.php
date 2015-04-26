<?php
/**
 * @author Kanstantsin A Kamkou
 * @link kkamkou at gmail dot com
 * @version $Id$
 */

class Cms_View_Helper_Debug extends Cms_View_Helper
{
    /**
     * Show GA script
     *
     * @return string HTML code of GA
     */
    public function debug()
    {
        if (!CMS_DEBUG) {
            return null;
        }
        
        $view = $this->getView();   
        
        $_ENV['CMS_TIME_END'] = microtime(true);
        
        return $view->render('debug.phtml');
    }
}
