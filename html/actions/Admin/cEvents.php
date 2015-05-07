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
 * @package  Admin
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

class Admin_cEvents extends Cms_Module_Admin
{
    protected $_accessList = array(
        'run' => array(
            '_ROLE' => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_EVENTS_VIEW
        ),

        'newAction' => array(
            '_ROLE' => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_EVENTS_NEW
        ),

        'editAction' => array(
            '_ROLE' => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_EVENTS_EDIT
        ),

        'orderAction' => array(
            '_ROLE' => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_EVENTS_EDIT
        ),

        'saveAction' => array(
            '_ROLE' => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_EVENTS_NEW
        ),

        'updateAction' => array(
            '_ROLE' => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_EVENTS_EDIT
        ),

        'removeAction' => array(
            '_ROLE' => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_EVENTS_REMOVE
        )
    );

    protected $_eventList = array(
        'saveAction'   => array('OnDynamicDataChanged', 'OnAdminEventsCreateComplete'),
        'updateAction' => array('OnDynamicDataChanged', 'OnAdminEventsUpdateComplete'),
        'removeAction' => array('OnDynamicDataChanged', 'OnAdminEventsRemoveComplete')
    );

    public function run()
    {
        // ordering mode
        $orderMode = isset($this->_input->order);

        $db = new Cms_Db_Events();

        // sort set
        $sortSet = array(
            'title'    => 'title',
            'location' => 'location',
            'start'    => 'timestamp_start',
            'end'      => 'timestamp_end',
            'author'   => 'user_name',
            'added'    => new Zend_Db_Expr('IF(ISNULL(updated), added, updated)')
        );
        $sortBy = null;
        if (!empty($this->_input->sortby)) {
            if (isset($sortSet[$this->_input->sortby])) {
                $this->_tpl->sortBy = $sortBy = $sortSet[$this->_input->sortby];
            }
        }

        // way set
        if ($sortBy) {
            $way = 'desc';
            if (!empty($this->_input->sortway)
                && in_array($this->_input->sortway, array('asc', 'desc'))) {
                $this->_tpl->sortway = $way = $this->_input->sortway;
            }
        }

        // query building
        $query = $db->select()->from(
            $db, array(
                'title', 'added', 'updated', 'user_name', 'active', 'id',
                'location', 'timestamp_start', 'timestamp_end'
            )
        );

        if (!empty($tags)) {
            $query->where('LOCATE(?, tags)', $tags);
        }

        // default order
        if (!$orderMode) {
            if (isset($way)) {
                $query->order("{$sortBy} {$way}");
            } else {
                $query->order('order ASC')->order("{$sortSet['added']} DESC");
            }
        } else {
            $query->order('order ASC');
            $this->_tpl->orderMode = true;
        }

        $this->_tpl->entries = $db->getPaginatorRows($query, $this->_input->sofar, 10);

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function newAction()
    {
        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function editAction()
    {
        $dialog = Cms_Dialog::getInstance();
        $db = new Cms_Db_Events();

        $entry = $db->getById(intval($this->_input->id));
        if (!$entry) {
            $dialog->construct(
                'MSG_ADMIN_EVENTS_DOESNT_EXIST', Cms_Dialog::TYPE_ERROR
            );
        }

        $entry = $entry->toArray();
        $entry['options']  = unserialize($entry['options']);

        $this->_tpl->entry = $entry;

        /* Output */
        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function updateAction()
    {
        $this->saveAction(true);
    }

    public function saveAction($updateMode = false)
    {
        $db = new Cms_Db_Events();
        $dialog = Cms_Dialog::getInstance();

        $hash = array(
            'title'       => $this->_input->event_title,
            'user_id'     => $this->_user->id,
            'user_name'   => $this->_user->name,
            'location'    => $this->_input->event_location,
            'active'      => intval($this->_input->event_active),
            'description' => $this->_input->_('event_text'),
            'type'        => $this->_input->event_type,
            'popup'       => intval($this->_input->event_popup),
            'options'     => serialize($this->_input->event_media)
        );

        // timestamp
        $locale = Cms_Locale::getTranslationList('datetime');
        foreach (array('timestamp_start', 'timestamp_end') as $tid) {
            if (empty($this->_input->{"event_{$tid}"})) {
                if ($updateMode) {
                    $hash[$tid] = null;
                }
                continue;
            }
            $timestamp = $this->_input->{"event_{$tid}"};
            if (!empty($this->_input->{"event_{$tid}_hour"})) {
                $timestamp .= ' ' . $this->_input->{"event_{$tid}_hour"};
                $timestamp .= ' ' . $this->_input->{"event_{$tid}_part"};
            }

            $date = new Cms_Date($timestamp, $locale['short']);
            $hash[$tid] = $date->getTimestamp();
        }

        // timestamp check
        if (isset($hash['timestamp_end']) || isset($hash['timestamp_start'])) {
            $time = time();
            $timeStart = isset($hash['timestamp_start']) ? $hash['timestamp_start'] : $time;
            $timeEnd = isset($hash['timestamp_end']) ? $hash['timestamp_end'] : $time;
            if ($time > $timeEnd) {
                $dialog->construct(
                    'MSG_ADMIN_EVENTS_WRONG_TIMESTAMP', Cms_Dialog::TYPE_ERROR
                );
            }
        }

        if (!$updateMode) {
            $db->insert($hash);

            $id = $db->getMaxId();

            $this->_events->OnAdminEventsCreateComplete
                ->addParams(array('title' => $hash['title'], 'id' => $id))
                ->execute();
        } else {
            $id = intval($this->_input->id);

            $db->update($hash, 'id = ' . $id);

            $this->_events->OnAdminEventsUpdateComplete
                ->addParams(array('title' => $hash['title'], 'id' => $id))
                ->execute();
        }

        $this->_events->OnDynamicDataChanged
            ->addParam('tags', array('static', 'page'))->execute();

        # Redirect to parent page
        $referer = Cms_Core::getReferer();
        if (isset($this->_input->saveAndGoList)) {
            if (stripos($referer, "open/admin-events") === false) {
                $referer = "/open/admin-events/";
            } else {
                $referer = preg_replace('~(&|\?)action=edit&id=\d+~', '', $referer);
            }
        }

        if (isset($this->_input->saveAndEdit)) {
            $referer = "/open/admin-events/?action=edit&id={$id}" .
                "&message_id=" . ($updateMode ? 'updated' : 'added') .
                "&referer=" . urlencode($referer);
        }

        Cms_Core::redirect($referer);
    }

    public function removeAction()
    {
        $dialog = Cms_Dialog::getInstance();
        $db = new Cms_Db_Events();
        $id = intval($this->_input->id);

        $entry = $db->getById($id);
        if (!$entry) {
            $dialog->construct(
                'MSG_ADMIN_EVENTS_DOESNT_EXIST', Cms_Dialog::TYPE_ERROR
            );
        }

        $title = $entry->title;

        $entry->delete();

        $this->_events->OnAdminEventsRemoveComplete
            ->addParams(array('title' => $title, 'id' => $id))->execute();

        $this->_events->OnDynamicDataChanged
            ->addParam('tags', array('static', 'page'))->execute();

        $dialog->construct(
            'MSG_ADMIN_EVENTS_REMOVED', Cms_Dialog::TYPE_SUCCESS
        );
    }

    /**
     * Reorders publications
     *
     * @return void
     * @todo move this method and similar method for static to
     *       the Strategy pattern (mb. other)
     */
    public function orderAction()
    {
        if (!$this->_input->isAjax()) {
            Cms_Core::e404();
        }

        // default set
        $dlg = Cms_Dialog::getInstance();
        $lng = Cms_Translate::getInstance();

        $mixedEntry = $this->_input->entry; // can be array or just id
        $perpage = intval($this->_input->perpage);
        $dropMode = ($this->_input->drop === 'true');

        $currentpage = intval($this->_input->currentpage);
        if ($currentpage > 0) {
            $currentpage -= 1;
        }

        // entries check
        if (!is_array($mixedEntry) && !$dropMode) {
            $dlg->construct(
                $lng->_('MSG_ADMIN_STATIC_WRONG_REORDER_PARAMS'), Cms_Dialog::TYPE_ERROR
            );
        }

        $db = new Cms_Db_Events();

        if (!$dropMode) {
            foreach ($mixedEntry as $newPos => $id) {
                $db->update(array('order' => $currentpage * $perpage + $newPos), 'id = ' . intval($id));
            }
        } else {
            $entry = $db->getById($mixedEntry);
            if (!$entry) {
                $dlg->construct(
                    $lng->_('MSG_ADMIN_STATIC_WRONG_REORDER_PARAMS'), Cms_Dialog::TYPE_ERROR
                );
            }

            $staticOld = $db->fetchRow(
                array('`order` = ?' => $static->order, 'type = ?' => $entry->type)
            );
            if (count($staticOld)) {
                $staticOld->order = $entry->order;
                $staticOld->save();
            }

            $maxOrder = $db->getAdapter()->fetchOne(
                $db->select()->from($db, new Zend_Db_Expr('MAX(`order`)'))
            );

            if ($this->_input->direction == 'next') {
                $newPos = ($currentpage + 1) * $perpage + 1;
            } else {
                $newPos = ($currentpage > 0 ? ($currentpage - 1) : 0) * $perpage;
            }

            if ($newPos > $maxOrder) {
                $newPos = $maxOrder + 1;
            }

            $db->update(array('order' => $newPos), "id = {$mixedEntry}");
        }

        // logging
        Cms_Logger::log('LOG_ADMIN_STATIC_REORDER', __CLASS__, __FUNCTION__);

        // message
        $dlg->construct(
            $lng->_('MSG_ADMIN_STATIC_ORDERED'), Cms_Dialog::TYPE_SUCCESS
        );

        // die
        Cms_Core::shutdown(true);
    }
}
