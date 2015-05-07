<?php
/**
 * 10 Denza
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

class cEdenza extends Cms_Module_Site
{
    public function run()
    {
        $db = new Cms_Db_Module_Ecommerce_Entries();

        $this->_tpl->paginator = $db->getPaginatorRows(
            $db->fetchAll(), intval($this->_input->getParam('item', 1)), 1, 1
        );

        return $this->_tpl->render($this->getTemplate(__FUNCTION__));
    }
}
