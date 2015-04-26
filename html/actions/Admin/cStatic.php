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

class Admin_cStatic extends Cms_Module_Admin
{
    const REWRITE_MASK = '\p{L}\p{N}\p{Z}_/.-';
    const MODULE_NONE = null;
    const MODULE_NEWS = 'news';

    protected $_accessList = array(
        'run' => array(
            '_ACCESS' => CMS_ACCESS_STATIC_VIEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'newAction' => array(
            '_ACCESS' => CMS_ACCESS_STATIC_NEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'saveAction' => array(
            '_ACCESS' => CMS_ACCESS_STATIC_NEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'editAction' => array(
            '_ACCESS' => CMS_ACCESS_STATIC_EDIT,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'autocompleteAction' => array(
            '_ACCESS' => CMS_ACCESS_STATIC_VIEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'updateAction' => array(
            '_ACCESS' => CMS_ACCESS_STATIC_EDIT,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            ),
        ),

        'removeAction' => array(
            '_ACCESS' => CMS_ACCESS_STATIC_REMOVE,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'orderAction' => array(
            '_ACCESS' => CMS_ACCESS_STATIC_EDIT,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        )
    );

    protected $_eventList = array(
        'saveAction'   => array('OnAdminStaticCreateComplete', 'OnDynamicDataChanged'),
        'updateAction' => array('OnAdminStaticUpdateComplete', 'OnDynamicDataChanged'),
        'removeAction' => array('OnAdminStaticRemoveComplete', 'OnDynamicDataChanged')
    );

    public function run()
    {
        $perPage = intval($this->_input->perpage);
        if ($perPage > 10 || $perPage == 0) {
            $perPage = 10;
        }

        // tables
        $db = new Cms_Db_Static();
        $udb = new Cms_Db_Users();

        $query = $db->select()->from(
            $db, array(
                'id', 'active', 'tags', 'hidden', 'rewrite', 'system',
                'title', 'added', 'updated', 'user_name', 'featured'
            )
        );

        // apply tags to the query
        $tags = Cms_Tags::decode($this->_input->tags, true);
        if (count($tags)) {
            foreach ($tags as $tag) {
                if (!empty($tag)) {
                    $query->where('LOCATE(?, tags)', "[{$tag}]");
                }
            }
        }

        // ordering mode
        $orderMode = !empty($this->_input->order);

        // building select query
        if ($orderMode) {
            $this->_tpl->orderMode = true;
            $perPage = -1;
            $query->order("order ASC");
        } else {
            $holders = array();
            if (@in_array('system', $this->_input->display)) {
                $holders['system'] = $this->_user->hasAcl(CMS_ACCESS_STATIC_SYSTEM);
            }

            if (@in_array('inactive', $this->_input->display)) {
                $holders['active'] = !$this->_user->hasAcl(CMS_ACCESS_STATIC_PUBLISH);
            }

            if (@in_array('hidden', $this->_input->display)) {
                $holders['hidden'] = $this->_user->hasAcl(CMS_ACCESS_STATIC_HIDE);
            }

            if (!count($holders)) {
                $query->where('active = ?', '1')
                    ->where('hidden = ?', '0')
                    ->where('system = ?', '0');
            } else {
                $where = '';
                foreach ($holders as $key => $v) {
                    $v = intval($v);
                    $where .= ($where == '' ? '' : 'OR ') . "{$key} = '{$v}' ";
                }
                $query->where($where);
            }

            // sort set
            $sortSet = array(
                'title'    => 'title',
                'address'  => 'rewrite',
                'author'   => 'user_name',
                'time'     => new Zend_Db_Expr(
                    'IF(ISNULL(updated), added, updated)'
                )
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

            // title search
            if ($this->_input->title != null) {
                $query->where(
                    'LOWER(title) RLIKE ?',
                    Cms_Functions::strtolower($this->_input->title)
                );
            }

            // ordering
            if (isset($way)) {
                $query->order("{$sortBy} {$way}");
            } else {
                $query->order("featured DESC, {$sortSet['time']} DESC");
            }
        }

        // paginator and entries
        $this->_tpl->paginator = $db->getPaginatorRows(
            $query, $this->_input->sofar, $perPage
        );

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function newAction()
    {
        /* Get layouts and templates */
        $this->_tpl->layouts = $this->_getListOfTplFiles('layouts');
        $this->_tpl->templates = $this->_getListOfTplFiles('scripts/cPages');

        /* Output */
        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function editAction()
    {
        $db = new Cms_Db_Static();

        $entry = $db->getById(intval($this->_input->id));
        if (!$entry) {
            Cms_Core::e404();
        }

        $dialog = Cms_Dialog::getInstance();

        // access check for system publications
        if ($entry->system && !$this->_user->hasAcl(CMS_ACCESS_STATIC_SYSTEM)) {
            $dialog->construct(
                'MSG_ADMIN_DIALOG_HAVENT_ACCESS', Cms_Dialog::TYPE_NOTICE
            );
        }

        // access check for hidden publications
        if ($entry->hidden && !$this->_user->hasAcl(CMS_ACCESS_STATIC_HIDE)) {
            $dialog->construct(
                'MSG_ADMIN_DIALOG_HAVENT_ACCESS', Cms_Dialog::TYPE_NOTICE
            );
        }

        $entry = $entry->toArray();
        $entry['options']  = unserialize($entry['options']);
        $entry['metadata'] = unserialize($entry['metadata']);
        $entry['tags']     = Cms_Tags::decode($entry['tags']);

        $this->_tpl->entry = $entry;

        /* Get layouts and templates */
        $this->_tpl->layouts = $this->_getListOfTplFiles('layouts');
        $this->_tpl->templates = $this->_getListOfTplFiles('scripts/cPages');

        /* Output */
        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));

        echo $this->_layout->render();
    }

    public function saveAction($upd = false, $usedByModule = self::MODULE_NONE)
    {
        $dlg = Cms_Dialog::getInstance();
        $lng = Cms_Translate::getInstance();

        // check if our rewrite is correct
        if (!preg_match('~^[' . self::REWRITE_MASK . ']+$~u', $this->_input->static_rewrite)) {
            $dlg->construct(
                $lng->_('MSG_ADMIN_STATIC_BAD_REWRITE'), Cms_Dialog::TYPE_ERROR
            );
        }

        // check if we have this publication in database
        $db = new Cms_Db_Static();

        $entry = $db->getByRewrite($this->_input->static_rewrite);
        if (!$upd && $entry) {
            $dlg->construct(
                $lng->_('MSG_ADMIN_STATIC_EXISTS'),
                Cms_Dialog::TYPE_ERROR,
                array('redirect' => '/open/admin-index/')
            );
        }

        // check if our static has title
        if (empty($this->_input->static_title)) {
            $dlg->construct(
                $lng->_('MSG_ADMIN_STATIC_BAD_TITLE'),
                Cms_Dialog::TYPE_ERROR
            );
        }

        // Parent check
        $parents = $this->_input->static_parents;
        if (is_array($this->_input->static_parents)) {
            foreach ($parents as &$v) {
                $v = trim(Cms_Functions::strtolower($v));
                if (!empty($v)
                    && !preg_match('~^[' . self::REWRITE_MASK . ']+$~u', $v)) {
                    $dlg->construct(
                        $lng->_('MSG_ADMIN_STATIC_BAD_PARENTS'),
                        Cms_Dialog::TYPE_ERROR
                    );
                }
            }
            $parents = '[' . implode('],[', $parents) . ']';
        }

        // html normalization
        $filter = new Cms_Filter_Purifier();

        // Hash for the insert or update
        $post = array(
            'title'     => $this->_input->static_title,
            'rewrite'   => $this->_input->static_rewrite,
            'lng'       => $this->_input->static_lng,
            'parents'   => $parents,
            'updated'   => time(),
            'content'   => $filter->filter($this->_input->_('static_content')),
            'featured'  => intval($this->_input->static_featured),
            'timestamp' => null,
            'tags'      => Cms_Functions::substr(
                Cms_Tags::encode($this->_input->static_tags), 0, 150
            )
        );

        // metadata
        $post['metadata'] = serialize(
            array(
                'title'       => $this->_input->static_seo_title,
                'description' => $this->_input->static_seo_descr,
                'keywords'    => Cms_Functions::strtolower($this->_input->static_seo_keywords),
                'robots'      => $this->_input->static_seo_robots,
                'author'      => $this->_input->static_seo_author
            )
        );

        // access group
        $post['group'] = null;
        if (is_array($this->_input->static_group) && !empty($this->_input->static_group[0])) {
            $post['group'] = implode(',', $this->_input->static_group);
        }

        // parses timestamp
        if (!empty($this->_input->static_timestamp)) {
            $locale = Cms_Locale::getTranslationList('date');
            if (Cms_Date::isDate($this->_input->static_timestamp, $locale['short'])) {
                $date = new Cms_Date($this->_input->static_timestamp, $locale['short']);
                $post['timestamp'] = $date->get(Cms_Date::TIMESTAMP);
            }
            unset($locale);
        }

        // news module area
        switch ($usedByModule) {
            case self::MODULE_NEWS:
                if ($this->_user->hasAcl(CMS_ACCESS_NEWS_PUBLISH)) {
                    $post['active'] = intval($this->_input->static_active);
                }
                break;
            default:
                if ($this->_user->hasAcl(CMS_ACCESS_STATIC_PUBLISH)) {
                    $post['active'] = intval($this->_input->static_active);
                }

                if ($this->_user->hasAcl(CMS_ACCESS_STATIC_HIDE)) {
                    $post['hidden'] = intval($this->_input->static_hidden);
                }

                if ($this->_user->hasAcl(CMS_ACCESS_STATIC_SYSTEM)) {
                    $post['system'] = intval($this->_input->static_system);
                }

                if ($this->_user->hasAcl(CMS_ACCESS_STATIC_OPTIONS)) {
                    // template and layout
                    $post['tpl'] = $this->_input->static_tpl;
                    $post['layout'] = $this->_input->static_layout;

                    // static options
                    $post['options'] = array();

                    if (isset($this->_input->options['sendheader'])
                        && is_numeric($this->_input->sendheader_code)) {
                        $post['options']['header']['code'] = $this->_input->sendheader_code;
                    }

                    // ajax layout
                    if ($this->_input->optionsUseAjaxLayout) {
                        if (!empty($this->_input->options['ajax']['layout'])) {
                            $post['options']['ajax']['layout'] = $this->_input
                                ->options['ajax']['layout'];
                        }
                    }

                    // ajax template
                    if ($this->_input->optionsUseAjaxTpl) {
                        if (!empty($this->_input->options['ajax']['tpl'])) {
                            $post['options']['ajax']['tpl'] = $this->_input
                                ->options['ajax']['tpl'];
                        }
                    }

                    $post['options'] = serialize($post['options']);
                }
                break;
        }

        $db = new Cms_Db_Static();

        // insert part
        if (!$upd) {
            // current user set
            $post['user_id'] = $this->_user->id;
            $post['user_name'] = $this->_user->name;

            // sql query
            $db->insert($post);

            // get current id
            $id = $db->getMaxId();

            // event for this part
            $this->_events->OnAdminStaticCreateComplete
                ->addParams(array('id' => $id, 'title' => $post['title']))
                ->execute();
        } else {
            // update part
            $id = (int)$this->_input->id;

            $db->update($post, "id = {$id}");

            // event for this part
            $this->_events->OnAdminStaticUpdateComplete
                ->addParams(array('id' => $id, 'title' => $post['title']))
                ->execute();
        }

        $this->_events->OnDynamicDataChanged
            ->addParam('tags', array('static', 'page'))->execute();

        # Redirect to parent page
        $referer = Cms_Core::getReferer();
        $module = $usedByModule ? $usedByModule : 'static';
        if (isset($this->_input->saveAndGoList)) {
            if (stripos($referer, "open/admin-{$module}") === false) {
                $referer = "/open/admin-{$module}/";
            } else {
                $referer = preg_replace('~(&|\?)action=edit&id=\d+~', '', $referer);
            }
        }

        if (isset($this->_input->saveAndPreview)) {
            $referer = "/{$post['rewrite']}.html";
        }

        if (isset($this->_input->saveAndEdit)) {
            $referer = "/open/admin-{$module}/?action=edit&id={$id}" .
                "&message_id=" . ($upd ? 'updated' : 'added') .
                "&referer=" . urlencode($referer);
        }

        Cms_Core::redirect($referer);
    }

    public function removeAction()
    {
        $id = intval($this->_input->id);
        $db = new Cms_Db_Static();

        $dlg = Cms_Dialog::getInstance();
        $lng = Cms_Translate::getInstance();

        // finding entries
        $entry = $db->getById($id);
        if (!$entry) {
            $dlg->construct(
                $lng->_('MSG_ADMIN_STATIC_NOT_EXISTS'), Cms_Dialog::TYPE_ERROR
            );
        }

        $title = $entry->title;

        if (!$entry->delete()) {
            $dlg->construct(
                $lng->_('MSG_ADMIN_STATIC_REMOVE_ERROR'), Cms_Dialog::TYPE_ERROR
            );
        }

        $sch = new Cms_Search_Static();
        $sch->docRemove($id);

        // event for this part
        $this->_events->OnAdminStaticRemoveComplete
            ->addParams(array('id' => $id, 'title' => $title))->execute();

        // checking referer
        $referer = Cms_Core::getReferer();
        if ($referer && strpos($referer, "id={$id}") !== false) {
            $referer = '/open/admin-static/';
        }

        # Redirect
        $dlg->construct(
            $lng->_('MSG_ADMIN_STATIC_REMOVED'), Cms_Dialog::TYPE_SUCCESS,
            array('redirect' => $referer)
        );
    }

    public function updateAction()
    {
        $this->saveAction(true);
    }

    public function autocompleteAction()
    {
        if (!$this->_input->isAjax()) {
            //Cms_Core::e404();
        }

        if (!in_array($this->_input->type, array('tags', 'rewrite'))) {
            Cms_Core::shutdown(true);
        }

        $type = $this->_input->type;
        $id = intval($this->_input->myid);

        // database select
        $db = new Cms_Db_Static();

        $query = $db->select()->distinct()
            ->from($db, array($this->_input->type))
            ->where('active = ?', '1')
            ->where('hidden = ?', '0');

        $exclude = array();
        if ($id) {
            $currentTags = $db->fetchRow(
                $db->select()->from($db, array($this->_input->type))->where('id = ?', $id)
            );

            $exclude = Cms_Tags::decode($currentTags[$this->_input->type], true);
        }

        // fetch tags
        $tags = $jsonSet = array();
        foreach ($db->fetchAll($query) as $v) {
            if (empty($v->{$type})) {
                continue;
            }
            $tags = array_merge($tags, Cms_Tags::decode($v->{$type}, true));
        }

        // cleanup and build json array
        $tags = array_unique($tags);
        foreach ($tags as $v) {
            if (!in_array($v, $exclude)) {
                $jsonSet[] = array('key' => $v, 'value' => $v);
            }
        }

        // json output
        echo Zend_Json::encode($jsonSet);

        Cms_Core::shutdown(true);
    }

    /**
     * Reorders publications
     *
     * @return void
     */
    public function orderAction()
    {
        if (!$this->_input->isAjax()) {
            Cms_Core::e404();
        }

        // default set
        $dlg = Cms_Dialog::getInstance();
        $lng = Cms_Translate::getInstance();

        $entry = $this->_input->entry; // can be array or just id
        $perpage = intval($this->_input->perpage);
        $dropMode = ($this->_input->drop === 'true');

        $currentpage = intval($this->_input->currentpage);
        if ($currentpage > 0) {
            $currentpage -= 1;
        }

        // entries check
        if (!is_array($entry) && !$dropMode) {
            $dlg->construct(
                $lng->_('MSG_ADMIN_STATIC_WRONG_REORDER_PARAMS'), Cms_Dialog::TYPE_ERROR
            );
        }

        $db = new Cms_Db_Static();

        if (!$dropMode) {
            foreach ($entry as $newPos => $id) {
                $db->update(array('order' => $currentpage * $perpage + $newPos), 'id = ' . intval($id));
            }
        } else {
            $static = $db->getById($entry);
            if (!$static) {
                $dlg->construct(
                    $lng->_('MSG_ADMIN_STATIC_WRONG_REORDER_PARAMS'), Cms_Dialog::TYPE_ERROR
                );
            }

            $staticOld = $db->fetchRow(
                array(
                    '`order` = ?' => $static->order,
                    'LOCATE(?, parents)' => $static->parents
                )
            );
            if (count($staticOld)) {
                $staticOld->order = $static->order;
                $staticOld->save();
            }

            $maxOrder = $db->getAdapter()->fetchOne(
                $db->select()->from($db, new Zend_Db_Expr('MAX(`order`)'))
                ->where('LOCATE(?, parents)', $static->parents)
            );

            if ($this->_input->direction == 'next') {
                $newPos = ($currentpage + 1) * $perpage + 1;
            } else {
                $newPos = ($currentpage > 0 ? ($currentpage - 1) : 0) * $perpage;
            }

            if ($newPos > $maxOrder) {
                $newPos = $maxOrder;
            }

            $db->update(array('order' => $newPos), "id = {$entry}");
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

    protected function _getListOfTplFiles($s)
    {
        $list = glob(CMS_ROOT . "templates/frontend/{$s}/*.phtml");
        foreach ($list as &$l) {
            $l = basename($l, '.phtml');
        }
        return $list;
    }
}
