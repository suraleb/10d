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

class cStatic extends Cms_Module_Site
{
    public function run($tpl = null)
    {
        $file = 'index.phtml';
        if ($tpl !== null || !empty($this->_input->path)) {
            $target = ($tpl !== null) ? $tpl : $this->_input->path;
            $target = strtolower($target);

            $path = CMS_ROOT . "templates/frontend/scripts/static/{$target}";
            if (!Cms_Filemanager::fileExists($path, true)) {
                Cms_Core::e404();
            }

            $file = $target;
        }

        $content = $this->_tpl->render("static/{$file}");
        if (!$this->_render) {
            return $content;
        }

        $this->_layout->content = $content;
        echo $this->_layout->render();
    }
}
