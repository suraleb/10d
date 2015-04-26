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

/**
 * @see Admin_cStatic
 */
require_once CMS_ROOT . 'actions/Admin/cStatic.php';

class Admin_cNews extends Cms_Module_Admin
{
    protected $_accessList = array(
        'run' => array(
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            ),
            '_ACCESS' => CMS_ACCESS_NEWS_VIEW
        ),

        'newAction' => array(
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            ),
            '_ACCESS' => CMS_ACCESS_NEWS_NEW
        ),

        'editAction' => array(
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            ),
            '_ACCESS' => CMS_ACCESS_NEWS_EDIT
        ),

        'updateAction' => array(
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            ),
            '_ACCESS' => CMS_ACCESS_NEWS_EDIT
        ),

        'removeAction' => array(
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            ),
            '_ACCESS' => CMS_ACCESS_NEWS_REMOVE
        ),

        'saveAction' => array(
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            ),
            '_ACCESS' => CMS_ACCESS_NEWS_NEW
        )
    );

    protected $_eventList = array(
        'saveAction'   => array('OnAdminStaticCreateComplete', 'OnDynamicDataChanged'),
        'updateAction' => array('OnAdminStaticUpdateComplete', 'OnDynamicDataChanged'),
        'removeAction' => array('OnAdminStaticRemoveComplete', 'OnDynamicDataChanged')
    );

    public function run()
    {
        // config and tags
        $tags = explode(
            ',', Cms_Tags::encode(Cms_Config::getInstance()->modules->news->tags)
        );

        // database get
        $db = new Cms_Db_Static();

        // ordering mode
        $orderMode = isset($this->_input->order);

        // sort set
        $sortSet = array(
            'title'  => 'title',
            'address'=> 'rewrite',
            'author' => 'user_name',
            'added'  => new Zend_Db_Expr('IF(ISNULL(updated), added, updated)')
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
            $db, array('user_name', 'rewrite', 'added', 'id', 'title', 'active', 'featured')
        );

        if (!empty($tags)) {
            $query->where('LOCATE(?, tags)', $tags);
        }

        // default order
        if (!$orderMode) {
            if (isset($way)) {
                $query->order("{$sortBy} {$way}");
            } else {
                $query->order("featured DESC, {$sortSet['added']} DESC");
            }
        } else {
            $query->order('order ASC');
            $this->_tpl->orderMode = true;
        }

        // paginator
        $this->_tpl->paginator = $db->getPaginatorRows(
            $query, $this->_input->sofar, 10
        );

        // output
        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function newAction()
    {
        $static = new Admin_cStatic();

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function removeAction()
    {
        require_once CMS_ROOT . 'actions/Admin/cStatic.php';
        $static = new Admin_cStatic();
        $static->removeAction();
    }

    public function saveAction($update = false)
    {
        // unset unused data
        foreach ($this->_input->getHash('post') as $k=>$v) {
            if (!preg_match('~^news_~', $k)) {
                continue;
            }
            $this->_input->{str_replace('news_', 'static_', $k)} = $v;
            unset($this->_input->{$k});
        }

        // config
        $config = Cms_Config::getInstance()->modules->news;

        // correcting input data
        $this->_input->static_rewrite = "news/{$this->_input->static_rewrite}";
        $this->_input->static_lng     = $this->_input->lng;
        $this->_input->static_tpl     = $config->tpl;
        $this->_input->static_layout  = $config->layout;
        $this->_input->static_parents = Cms_Tags::encode($config->parents);
        $this->_input->static_group   = 'all';

        // static layer
        $static = new Admin_cStatic();
        $static->saveAction($update, Admin_cStatic::MODULE_NEWS);
    }

    public function editAction()
    {
        $db = new Cms_Db_Static();

        $entry = $db->getById(intval($this->_input->id));
        if (!$entry) {
            Cms_Core::e404();
        }

        $entry = $entry->toArray();
        $entry['options']  = unserialize($entry['options']);
        $entry['metadata'] = unserialize($entry['metadata']);
        $entry['tags']     = Cms_Tags::decode($entry['tags']);

        $static = new Admin_cStatic();

        $this->_tpl->entry = $entry;

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function updateAction()
    {
        $this->saveAction(true);
    }
}
