<?php
/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Admin
 * @package  Modules
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

class Admin_cPoll extends Cms_Module_Admin
{
    protected $_accessList = array(
        'run' => array(
            '_ACCESS' => CMS_ACCESS_POLL_VIEW,
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER)
        ),

        'newAction' => array(
            '_ACCESS' => CMS_ACCESS_POLL_NEW,
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER)
        ),

        'saveAction' => array(
            '_ACCESS' => CMS_ACCESS_POLL_NEW,
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER)
        ),

        'removeAction' => array(
            '_ACCESS' => CMS_ACCESS_POLL_REMOVE,
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER)
        ),

        'editAction' => array(
            '_ACCESS' => CMS_ACCESS_POLL_EDIT,
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER)
        ),

        'updateAction' => array(
            '_ACCESS' => CMS_ACCESS_POLL_EDIT,
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER)
        )
    );

    public function run()
    {
        $db = new Cms_Db_Polls();

        $enties = $db->fetchAll();
        $this->_tpl->polls = $enties->toArray();

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        $this->_messageManager->dispatch(true);

        echo $this->_layout->render();
    }

    public function newAction()
    {
        $this->_layout->content = $this->_tpl->render($this->getTemplate(__FUNCTION__));
        echo $this->_layout->render();
    }

    public function updateAction()
    {
        $this->saveAction(true);
    }

    public function editAction()
    {
        $id = intval($this->_input->id);

        $dialog = Cms_Dialog::getInstance();
        $db = new Cms_Db_Polls();

        $entry = $db->getById($id);
        if (!$entry) {
            $dialog->construct(
                'MSG_ADMIN_POLL_ERROR_EXISTS', Cms_Dialog::TYPE_ERROR
            );
        }

        $entry = $entry->toArray();
        $entry['choices'] = unserialize($entry['choices']);

        $this->_tpl->poll = $entry;

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function saveAction($upd = false)
    {
        if (!$this->_input->isPost()) {
            Cms_Core::e403();
        }

        $dialog = Cms_Dialog::getInstance();

        $_question = Cms_Functions::strip_tags($this->_input->poll_question);
        if (empty($_question)) {
            $dialog->construct(
                'MSG_ADMIN_POLL_ERROR_NO_QUESTION',
                Cms_Dialog::TYPE_ERROR
            );
        }

        $db = new Cms_Db_Polls();

        if (!$upd) {
            $entry = $db->getByQuestion($_question);
            if ($entry) {
                $dialog->construct(
                    'MSG_ADMIN_POLL_ALREADY_EXISTS',
                    Cms_Dialog::TYPE_ERROR
                );
            }
        } else {
            $id = intval($this->_input->id);
            $entry = $db->getById($id);
            if (!$entry) {
                $dialog->construct(
                    'MSG_ADMIN_POLL_ERROR_EXISTS',
                    Cms_Dialog::TYPE_ERROR
                );
            }
        }

        if ($this->_input->poll_choices[1] == '') {
            $dialog->construct(
                'MSG_ADMIN_POLL_ERROR_NO_VARIANTS',
                Cms_Dialog::TYPE_ERROR
            );
        }

        $_choices = $this->_buildChoices($this->_input->poll_choices, $this->_input->poll_votes);
        $array = array(
            'question' => $_question,
            'active'   => intval($this->_input->poll_active),
            'group'    => $this->_input->poll_group,
            'choices'  => serialize($_choices)
        );

        if (!$upd) {
            $array['type'] = $this->_input->poll_type;
            $array['user_id'] = $this->_user->id;
        }

        if (!$upd) {
            $db->insert($array);

            // cleaning cache
            $this->_cleanupCache();

            Cms_Logger::log(
                array(
                    'LOG_ADMIN_POLL_NEW',
                    Cms_Functions::substr($array['question'], 0, 150)
                ), __CLASS__, __FUNCTION__
            );

            $this->_messageManager->store(
                Cms_Message_Format_Plain::success($lng->_('MSG_ADMIN_POLL_SAVED'))
            );
        }

        $db->update($array, "id = {$id}");

        // cleaning cache
        $this->_cleanupCache();

        // log
        Cms_Logger::log(
            array(
                'LOG_ADMIN_POLL_UPDATE',
                Cms_Functions::substr($array['question'], 0, 150),
                $id
            ), __CLASS__, __FUNCTION__
        );

        // redirecting
        $lng = Cms_Translate::getInstance();

        $this->_messageManager->store(
            Cms_Message_Format_Plain::success($lng->_('MSG_ADMIN_POLL_UPDATED'))
        );

        Cms_Core::redirect('/open/admin-poll/');
    }

    protected function _buildChoices(array $arr, array $votes)
    {
        $_choices = array();
        foreach ($arr as $k => $v) {
            $v = Cms_Functions::strip_tags($v);
            if ($v == '') {
                continue;
            }
            $_choices['choices'][] = array(
                'text' => $v, 'votes' => $votes[$k]
            );
        }

        return $this->_calculate($_choices);
    }

    protected function _calculate(array $array)
    {
        $array['total'] = 0;
        foreach ($array['choices'] as $v) {
            $array['total'] += $v['votes'];
        }

        $array['percentmax'] = 0;
        foreach ($array['choices'] as &$v) {
            $_perc = ($array['total'] > 0) ? (100 * $v['votes'] / $array['total']) : 0;
            $v['percent'] = sprintf("%02.01f", $_perc);
            if ($_perc > $array['percentmax']) {
                $array['percentmax'] = $v['percent'];
            }
        }

        return $array;
    }

    public function removeAction()
    {
        $id = intval($this->_input->id);

        $db = new Cms_Db_Polls();
        $entry = $db->getById($id);

        $dialog = Cms_Dialog::getInstance();
        if (!$entry) {
            $dialog->construct(
                'MSG_ADMIN_POLL_ERROR_EXISTS',
                Cms_Dialog::TYPE_ERROR
            );
        }

        $title = Cms_Functions::substr($entry->question, 0, 150);

        $entry->delete();

        // log
        Cms_Logger::log(
            array('LOG_ADMIN_POLL_REMOVE', $title, $id), __CLASS__, __FUNCTION__
        );

        // cleaning cache
        $this->_cleanupCache();

        // redirecting
        $dialog->construct(
            'MSG_ADMIN_POLL_REMOVED',
            Cms_Dialog::TYPE_SUCCESS,
            array('redirect' => '/open/admin-poll/')
        );
    }

    protected function _cleanupCache()
    {
        if (Cms_Config::getInstance()->cms->cache->disabled) {
            return false;
        }
        $ch = new Cms_Cache_File();
        return $ch->clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('poll')
        );
    }
}
