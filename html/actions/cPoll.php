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
 */

class cPoll extends Cms_Module_Site
{
    /**
     * Access list
     *
     * @var array
     */
    protected $_accessList = array(
        'run'        => array('_ROLE' => '*', '_ACCESS' => '*'),
        'voteAction' => array('_ROLE' => '*', '_ACCESS' => '*')
    );

    /**
     * Default action
     *
     * @return void
     */
    public function run()
    {
        Cms_Core::redirect();
    }

    /**
     * Vote calculation and save
     *
     * @return void
     */
    public function voteAction()
    {
        if (!isset($_SERVER['HTTP_REFERER'])
            || stripos($_SERVER['HTTP_REFERER'], CMS_HOST) !== 0
            || !$this->_input->isPost()) {
            Cms_Core::redirect();
        }

        $backUrl = str_replace(CMS_HOST, '', $_SERVER['HTTP_REFERER']);
        $id = intval($this->_input->id);

        $poll = $this->fetchPoll($id, true);
        if (!$poll) {
            Cms_Core::e404();
        }

        if ($poll['_hash']['_voted']) {
            Cms_Core::redirect($backUrl);
        }

        $poll['_obj']->iplist .= ',' . $_SERVER['REMOTE_ADDR'];
        $poll['_obj']->iplist  = ltrim($poll['_obj']->iplist, ',');

        $_choice = $this->_input->vote_choice;

        if ($poll['_hash']['type'] != 'multi') {
            if (!is_numeric($_choice)
                || $_choice > 6
                || !isset($poll['_hash']['choices']['choices'][$_choice])) {
                Cms_Core::redirect($backUrl);
            }
            $poll['_hash']['choices']['choices'][$_choice]['votes'] += 1;
        } else {
            if (!is_array($_choice)) {
                Cms_Core::redirect($backUrl);
            }
            foreach ($_choice as $ch) {
                if ($ch > 6
                    || !isset($poll['_hash']['choices']['choices'][$ch])) {
                    Cms_Core::e404();
                }
                $poll['_hash']['choices']['choices'][$ch]['votes'] += 1;
            }
        }
        $poll['_hash']['choices']['total'] += 1;

        $_max = 0;
        $_total = $poll['_hash']['choices']['total'];
        foreach ($poll['_hash']['choices']['choices'] as $k => &$v) {
            $_perc = $_total > 0 ? (100 / $_total * $v['votes']) : 0;
            $v['percent'] = sprintf("%02.01f", $_perc);
            if ($_perc > $_max) {
                $_max = $v['percent'];
            }
        }

        $poll['_hash']['choices']['percentmax'] = $_max;
        $poll['_obj']->choices = serialize($poll['_hash']['choices']);
        $poll['_obj']->save();

        // cookie set
        $cookie = Cms_Core::getCookie('polls');
        Cms_Core::setCookie('polls', ltrim("{$cookie},{$id}", ','));

        // cache clean
        if (!Cms_Config::getInstance()->cms->cache->disabled) {
            $ch = new Cms_Cache_File();
            $ch->clean(
                Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                array('poll', 'static')
            );
        }

        Cms_Core::redirect($backUrl);
    }

    /**
     * Gets poll from database. Checks ip and cookie.
     *
     * @param int $id
     * @param bool $returnWithObject (Default: false)
     * @return array
     */
    public function fetchPoll($id, $returnWithObject = false)
    {
        $db = new Cms_Db_Polls();

        // poll find
        $entry = $db->getById(intval($id));
        if (!$entry) {
            return false;
        }

        // default set
        $array = $entry->toArray();
        $array['_voted'] = false;
        $array['choices'] = unserialize($array['choices']);

        // access and cookies check
        if ($array['group'] != 'all') {
            if ($this->_user->role != $array['group']) {
                $array['_voted'] = true;
            }
        } else {
            $_cookie = explode(',', Cms_Core::getCookie('polls'));
            if (strpos($array['iplist'], $_SERVER['REMOTE_ADDR']) !== false
                || in_array($id, $_cookie)) {
                $array['_voted'] = true;
            }
        }

        // hash build
        if ($returnWithObject) {
            return array('_hash' => $array, '_obj' => $entry);
        }
        return $array;
    }
}
