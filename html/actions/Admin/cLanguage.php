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
 * @version  $Id$
 */

class Admin_cLanguage extends Cms_Module_Admin
{
    # module version
    const VERSION = '0.1';

    protected $_accessList = array(
        'run' => array(
            '_ACCESS' => CMS_ACCESS_LANGUAGE_VIEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'viewAction' => array(
            '_ACCESS' => CMS_ACCESS_LANGUAGE_VIEW,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        ),

        'updateAction' => array(
            '_ACCESS' => CMS_ACCESS_LANGUAGE_EDIT,
            '_ROLE' => array(
                CMS_USER_ADMIN, CMS_USER_MEMBER
            )
        )
    );

    protected $_lngDir = '';

    protected $_mask = '~^[\w\d-]+$~';

    public function __construct()
    {
        parent::__construct();

        $this->_lngDir = CMS_ROOT . 'languages';
    }

    public function run()
    {
        $l = glob($this->_lngDir . CMS_SEP . '*.tmx');

        $t = array();

        foreach ($l as &$v) {
            $v = preg_replace('~\.tmx$~', '', basename($v, '.tmx'));

            $t[$v] = filemtime($this->_lngDir . CMS_SEP . $v . '.tmx');
        }

        ksort($l);

        $this->_tpl->time = $t;
        $this->_tpl->files = $l;

             $this->_layout->content = $this->_tpl->render($this->getTemplate(__FUNCTION__));
        echo $this->_layout->render();
    }

    public function viewAction()
    {
        $f = $this->_input->file;

        if (!preg_match($this->_mask, $f)) {
            Cms_Core::e404();
        }

        $f = $this->_lngDir . CMS_SEP . $f . '.tmx';

        if (!Cms_Filemanager::fileExists($f)) {
            Cms_Dialog::getInstance()->construct('MSG_ADMIN_LANGUAGE_ERROR_OPEN_EXISTS', Cms_Dialog::TYPE_ERROR);
        }

        $tmx = new Cms_Tmx();

        if (!$tmx->setData(Cms_Filemanager::fileRead($f))) {
            Cms_Dialog::getInstance()->construct(
                array('MSG_ADMIN_LANGUAGE_ERROR_OPEN_READ', $tmx->getError()),
                Cms_Dialog::TYPE_ERROR
            );
        }

        $l = $tmx->getList();

        foreach ($l as $k => &$v) {
            if ($k == 'ru') {
                continue;
            }
            foreach (array_diff_key($l['ru'], $l[$k]) as $nk=>$nv) {
                $v[$nk] = $nv;
            }
        }

        $this->_tpl->entries = $l;

        $this->_layout->content = $this->_tpl
            ->render($this->getTemplate(__FUNCTION__));
        echo $this->_layout->render();
    }

    public function updateAction()
    {
        $f = $this->_input->file;
        if (!preg_match($this->_mask, $f)) {
            Cms_Core::e404();
        }

        $dialog = Cms_Dialog::getInstance();

        $f = $this->_lngDir . CMS_SEP . $f . '.tmx';
        if (!Cms_Filemanager::fileExists($f)) {
            $dialog->construct(
                'MSG_ADMIN_LANGUAGE_ERROR_OPEN_EXISTS', Cms_Dialog::TYPE_ERROR
            );
        }

        if (!is_array($this->_input->lngEntry)) {
            Cms_Core::e404();
        }

        $tpl  = '<?xml version="1.0"?>' . "\n";
        $tpl .= '<!DOCTYPE tmx SYSTEM "tmx14.dtd">' . "\n";
        $tpl .= '<tmx version="1.4">' . "\n";
        $tpl .= '<header creationtoolversion="' . Cms_Version::VERSION . '"
                    datatype="winres" segtype="sentence" adminlang="en"
                    srclang="en" o-tmf="abc" creationtool="kkcms">
                    </header>' . "\n";
        $tpl .= '<body>' . "\n";

        foreach ($this->_input->_('lngEntry') as $id => $a) {
            if (!preg_match($this->_mask, $id)) {
                continue;
            }

            $tpl .= "\n\n<tu tuid='" . $id . "'>";

            foreach ($a as $l => $v) {
                $tpl .= "\n\t<tuv xml:lang=\"" . $l . "\">";
                $tpl .= "<seg><![CDATA[" . $v . "]]></seg>";
                $tpl .= "</tuv>";
            }

            $tpl .= "\n</tu>";
        }

        $tpl .= "\n\n</body></tmx>\n";

        if (!Cms_Filemanager::fileWrite($f, $tpl)) {
            $dialog->construct(
                'MSG_ADMIN_LANGUAGE_ERROR_FILEWRITE', Cms_Dialog::TYPE_ERROR
            );
        };

        # Log
        Cms_Logger::log(array('LOG_ADMIN_LANGUAGE_UPDATED', $this->_input->file), __CLASS__, __FUNCTION__);

        # Redirect
        $dialog->construct(
            'MSG_ADMIN_LANGUAGE_UPDATE_SUCCESS',
            Cms_Dialog::TYPE_SUCCESS,
            array(
                'redirect' => "/open/admin-language/?action=view&file={$this->_input->file}"
            )
        );
    }

    /*protected function checkLngId($s)
    {
        $ar = array('TXT', 'ENV', 'LOG', 'LBL', 'MSG', 'ACCESS', 'CMS_ACCESS');

        foreach ($ar as $v) {
            if (preg_match('~^' . $v . '~', $s)) {
                return true;
            }
        }

        return false;
    }*/
}
