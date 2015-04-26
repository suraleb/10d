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

Zend_Loader::loadFile('cStatic.php', CMS_ROOT . 'actions');

class cDefault extends Cms_Module_Site
{
    protected $_accessList = array(
        'run'           => array('_ROLE' => '*', '_ACCESS' => '*'),
        'htcacheAction' => array('_ROLE' => '*', '_ACCESS' => '*'),
        'thumbAction'   => array('_ROLE' => '*', '_ACCESS' => '*')
    );

    public function run()
    {
        if (isset($this->_input->mdl) && $this->_input->action == null) {
            Cms_Core::redirect();
        }


        $this->_tpl->entries = Cms_Filemanager::dirList(CMS_UPL . 'public/homepage');

        $static = new cStatic($this->_render);
        $static->run();
    }

    public function htcacheAction()
    {
        if (!$this->_input->isGet()) {
            Cms_Core::e404();
        }

        $pr = new Cms_Cache_Htdocs($this->_input->files, $this->_input->type);
        echo $pr->getContent();

        Cms_Core::shutdown(true);
    }

    public function thumbAction()
    {
        $f = $this->_input->file;
        if (!$f) {
            Cms_Core::e404();
        }

        $p = CMS_UPL . "type-image/{$f}";
        if (!Cms_Filemanager::fileExists($p, true)) {
            Cms_Core::sndHeaderCode(404);
            Cms_Core::shutdown(true);
        }

        $t = new Cms_Thumbnail($p, $this->_input->w, $this->_input->h);
        $t->setOption('crop', !empty($this->_input->crop));
        $t->show();

        Cms_Core::shutdown(true);
    }
}
