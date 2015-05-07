<?php
/**
 * Content Management System
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

/**
* @see Cms_Module_Site
*/
class cPages extends Cms_Module_Site
{
    /**
     * Pager id for an URL in pagination
     *
     * @var string
     */
    const PARSER_PAGER_ID = 'part';

    /**
     * Creates content according settings of page
     *
     * @return void
     */
    public function run()
    {
        // building page id
        $pageId = $this->_input->rewrite;
        if (!isset($pageId)) {
            $pageId = rawurldecode($_SERVER['REQUEST_URI']);
        }

        // IIS translation
        $pageId = Utf8::is_utf8($pageId) ? $pageId : Utf8::convert_from($pageId);
        $pageId = Cms_Functions::strtolower(trim($pageId, '/'));
        $pageId = preg_replace('~\.html(\?.+)?$~', '', $pageId);

        // checks if we have rewrite
        if (empty($pageId)) {
            Cms_Core::redirect();
        }

        $db = new Cms_Db_Static();

        // loads static page from the database
        $static = $db->getByRewrite($pageId);
        if (!$static) {
            Cms_Core::e404();
        }

        // building hash
        $entry = $static->toArray();

        // access group check
        if ($entry['group'] && $this->_user->role != CMS_USER_ADMIN) {
            if (strpos($entry['group'], $this->_user->role) === false) {
                Cms_Core::e403();
            }
        }

        // is this page active?
        if (!$entry['active']) {
            if (!Cms_User::getInstance()->hasAcl(CMS_ACCESS_STATIC_PUBLISH)) {
                Cms_Core::e404();
            }
        }

        // is this page hidden?
        if ($entry['hidden']) {
            if ($this->_render && !Cms_User::getInstance()->hasAcl(CMS_ACCESS_STATIC_HIDE)) {
                Cms_Core::e404();
            }
        }

        // counter update
        if ($this->_render) {
            $static->incViewsCount();
        }

        // page information
        $entry['options']  = unserialize($entry['options']);
        $entry['metadata'] = unserialize($entry['metadata']);

        // custom header
        if (isset($entry['options']['header'])) {
            if (isset($entry['options']['header']['code'])) {
                Cms_Core::sndHeaderCode($entry['options']['header']['code']);
            }
        }

        // text parsing and pagination
        $parser = new Cms_Text_Parser($entry['content']);
        $parser->setPartNumber(intval($this->_input->{self::PARSER_PAGER_ID}));
        $entry['content'] = $parser->getText();

        // template and layout
        $template = $entry['tpl'];
        $layout = $entry['layout'];

        // ajax request?
        if ($this->_input->isAjax()) {
            // changing template
            if (!empty($entry['options']['ajax']['tpl'])) {
                $template = $entry['options']['ajax']['tpl'];
            }

            // changing layout
            if (!empty($entry['options']['ajax']['layout'])) {
                $layout = $entry['options']['ajax']['layout'];
            }
        }

        // display content if page hasn't template
        if ($template == 'none') {
            if (!$this->_render) {
                return $entry['content'];
            }
            echo $entry['content'];
            return;
        }

        // parents
        if (!empty($entry['parents'])) {
            $parents = $db->fetchParents($entry['parents']);
            if (count($parents)) {
                $this->_tpl->parents = $parents->toArray();
            }
        }

        // meta information for the entry
        $metaData = $entry['metadata'];
        if (empty($metaData['keywords'])) {
            if (strpos($entry['tags'], '[system]') === false) {
                $metaData['keywords'] = Cms_Tags::decode($entry['tags']);
            }
        }

        // entry defaults
        $this->_tpl->metaData        = $metaData;
        $this->_tpl->entry           = $entry;
        $this->_tpl->entryParts      = $parser->getPartsCount();
        $this->_tpl->entryPartNumber = $parser->getPartNumber();

        // admin area for the publication
        if ($this->_render) {
            $this->_tpl->renderToPlaceholder('page-details.phtml', 'pageDetails');
            $this->_tpl->renderToPlaceholder('page-admin.phtml', 'pageAdmin');
        }

        // content of the entry
        $content = $this->_tpl->render($this->getTemplate($template));

        // return content if parser used and no layout
        if ($layout == 'none') {
            if (!$this->_render) {
                return $content;
            }
            echo $content;
            return;
        }

        // adding layout and content
        $this->_layout->setLayout($layout)->content = $content;

        // return content if parser used
        if (!$this->_render) {
            return $this->_layout->render();
        }

        // just show it
        echo $this->_layout->render();
    }
}
