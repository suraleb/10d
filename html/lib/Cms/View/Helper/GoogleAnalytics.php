<?php
/**
 * @author Kanstantsin A Kamkou
 * @link kkamkou at gmail dot com
 * @version $Id$
 */

class Cms_View_Helper_GoogleAnalytics extends Cms_View_Helper
{
    /**
     * Show GA script
     *
     * @return string HTML code of GA
     */
    public function googleAnalytics()
    {
        $view = $this->getView();

        // skip it for the testing environments
        $cfg = $view->config->modules->analytics;
        if ($cfg->disabled) {
            return "<!-- google analytics skipped -->\n";
        }

        // if no ID provided
        if (empty($cfg->id)) {
            return 'You must provide Google Analytics ID or disable it';
        }
        $view->ga = $cfg->id;
        return $view->render('google-analytics.phtml');
    }

}
