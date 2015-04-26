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
 */

/**
 * @see Cms_View_Helper
 */
class Cms_View_Helper_Social extends Cms_View_Helper
{
    /**
     * Adds social data to html output
     *
     * @param string $url
     * @return string
     */
    public function social($url)
    {
        // view object
        $view = $this->getView();

        // default set
        $view->url = $url;
        $view->lang = Cms_Translate::getInstance()->_('LOCALE');

        $view->headScript()
            ->appendFile('https://platform.twitter.com/widgets.js')
            ->appendFile('https://apis.google.com/js/plusone.js')
            ->appendFile("https://connect.facebook.net/{$view->lang}/all.js#xfbml=1");

        // output
        return $view->render('social.phtml');
    }
}
