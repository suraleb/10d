<?php
/**
 * Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Ecommerce
 * @package  Admin
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 */

/**
 * @see Cms_Module_Admin
 */
class Admin_cEcommerce extends Cms_Module_Admin
{
    protected $_accessList = array(
        'run' => array(
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_ECOMMERCE_VIEW
        ),

        'productsAction' => array(
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_ECOMMERCE_PRODUCTS_VIEW
        ),

        'prodformAction' => array(
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_ECOMMERCE_PRODUCT_NEW
        ),

        'prodsaveAction' => array(
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_ECOMMERCE_PRODUCT_NEW
        ),

        'prodeditAction' => array(
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_ECOMMERCE_PRODUCT_EDIT
        ),

        'prodremoveAction' => array(
            '_ROLE'   => array(CMS_USER_ADMIN, CMS_USER_MEMBER),
            '_ACCESS' => CMS_ACCESS_ECOMMERCE_PRODUCT_REMOVE
        )
    );

    public function run()
    {
        // output
        $this->_layout->content = $this->_tpl->render(
            $this->getTemplate(__FUNCTION__)
        );
        echo $this->_layout->render();
    }

    public function productsAction()
    {
        $db = new Cms_Db_Module_Ecommerce_Entries();

        $this->_tpl->paginator = $db->getPaginatorRows(
            $db->getQueryAdminList(), $this->_input->getParam('sofar', 0), 15
        );

        // output
        $this->_layout->content = $this->_tpl->render(
            $this->getTemplate(__FUNCTION__)
        );
        echo $this->_layout->render();
    }

    public function prodformAction(array $data = array())
    {
        // data provider
        $this->_tpl->data = $data;

        // if we have messages we should display them
        $this->_messageManager->dispatch(true);

        // output
        $this->_layout->content = $this->_tpl->render(
            $this->getTemplate(__FUNCTION__)
        );

        echo $this->_layout->render();
    }

    public function prodeditAction()
    {
        $db = new Cms_Db_Module_Ecommerce_Entries();

        $entry = $db->getById(intval($this->_input->id));
        if (!$entry) {
            return Cms_Core::redirect('/open/admin-ecommerce/');
        }

        $model = new Cms_Model_Ecommerce_Entry();
        $model->import($entry);

        return $this->prodformAction($model->getAllFields());
    }

    public function prodremoveAction()
    {
        $lng = Cms_Translate::getInstance();
        $db = new Cms_Db_Module_Ecommerce_Entries();

        $entry = $db->getById(intval($this->_input->id));
        if (!$entry) {
            return Cms_Core::ajaxError($lng->_('MSG_ADMIN_ECOMMERCE_PRODUCT_NOT_EXISTS'));
        }

        $entry->delete();

        return Cms_Core::ajaxSuccess($result = array(
            'msg' => $lng->_('MSG_ADMIN_ECOMMERCE_PRODUCT_REMOVED')
        ));
    }

    public function prodsaveAction()
    {
        $model = new Cms_Model_Ecommerce_Entry();
        $db = new Cms_Db_Module_Ecommerce_Entries();

        if (isset($this->_input->id)) {
            $entry = $db->getById(intval($this->_input->id));
            if (!$entry) {
                return Cms_Core::redirect('/open/admin-ecommerce/');
            }

            $model->import($entry);
        }

        $model->setTitle($this->_input->productTitle)
            ->setDescription($this->_input->_('productDescr'))
            ->setCover($this->_input->productCover);

        if (!$model->isValid()) {
            $lng = Cms_Translate::getInstance();

            foreach ($model->getMessages() as $msg) {
                $this->_messageManager->store(
                    Cms_Message_Format_Plain::error($lng->_($msg))
                );
            }

            return $this->prodformAction($model->getAllFields());
        }

        $model->save();

        # Redirect to the parent page
        $referer = Cms_Core::getReferer();

        if (isset($this->_input->saveAndGoList)) {
            if (stripos($referer, "open/admin-ecommerce") === false) {
                $referer = "/open/admin-ecommerce/";
            } else {
                $referer = preg_replace('~(&|\?)action=edit&id=\d+~', '', $referer);
            }
        }

        if (isset($this->_input->saveAndPreview)) {
            $referer = "/ecommerce/{$model->getId()}.html";
        }

        if (isset($this->_input->saveAndEdit)) {
            $this->_messageManager->store(
                Cms_Message_Format_Plain::success(
                    Cms_Translate::getInstance()->_('MSG_ADMIN_ECOMMERCE_PRODUCT_SAVED')
                )
            );
            return $this->prodformAction($model->getAllFields());
        }

        Cms_Core::redirect($referer);
    }
}
