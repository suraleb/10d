<?php
/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Module
 * @package  Site
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

/**
 * @see Cms_Module_Site
 */
class cSearch extends Cms_Module_Site
{
    /**
     * Default form
     *
     * @return void
     */
    public function run()
    {
        $results = $this->_fetchResults();
        if ($results) {
            $this->_tpl->entries = $results;
        }

        $content = $this->_tpl->render($this->getTemplate(__FUNCTION__));
        if (!$this->_render) {
            return $content;
        }

        $this->_layout->content = $content;

        echo $this->_layout->render();
    }

    /**
     * Search and returns result as object
     *
     * @return object
     */
    protected function _fetchResults()
    {
        $query = $this->_input->_('q');
        if ($query === null) {
            return null;
        }

        // check query length
        $strlen = Cms_Functions::strlen($query);
        if ($strlen < 3 || $strlen > 255) {
            $this->_tpl->message = 'TXT_SITE_SEARCH_WRONG_LENGTH';
            return null;
        }

        // search
        $search = new Cms_Search_Static();

        // check format
        $results = $search->search($query);
        if ($results instanceof Zend_Search_Lucene_Exception) {
            $this->_tpl->message = 'TXT_SITE_SEARCH_WRONG_QUERY';
            if (CMS_DEBUG) {
                Cms_Core::log(
                    "Got an exception '{$results->getMessage()}' while searching",
                    Zend_Log::NOTICE
                );
            }
            return null;
        }

        // check results count
        if (!count($results)) {
            $this->_tpl->message = 'TXT_SITE_SEARCH_NOTHING_FOUND';
            return null;
        }

        return $results;
    }
}
