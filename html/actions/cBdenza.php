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

class cBdenza extends Cms_Module_Site
{
    protected $_accessList = array(
        'run' => array('_ROLE' => '*', '_ACCESS' => '*'),

        'sendmailAction' => array('_ROLE' => '*', '_ACCESS' => '*')
    );

    public function run()
    {
        $db = new Cms_Db_Static();

        $this->_tpl->paginator = $db->getPaginatorRows(
            $db->fetchByTag('propaganda', null, null, 'added', 'desc'), intval($this->_input->getParam('page', 1)), 2, 1
        );

        return $this->_tpl->render($this->getTemplate(__FUNCTION__));
    }

    public function sendmailAction()
    {
        $email = $this->_input->email;

        $validator = new Zend_Validate_EmailAddress(array('deep' => true));
        if (!$validator->isValid($email)) {
            Cms_Core::ajaxError('Incorrect e-mail address provided.');
        }

        $this->_tpl->email = $email;
        $this->_tpl->message = $this->_tpl->render($this->getTemplate('mail'));

        $mail = new Zend_Mail('UTF-8');
        $mail->setFrom('noreply@10denza.com', '10denza');

        $mail->addTo('info@10denza.com', '10denza');
        $mail->addCc($this->_input->email);
        $mail->addBcc('dave@launch365.com', 'Dave');
        $mail->addBcc('chris@10denza.com', 'Chris');

        $mail->setSubject('10denza Sign Up');

        $mail->setBodyHtml($this->_tpl->render('mail.phtml'));

        try {
            $mail->send();
        } catch (Zend_Mail_Transport_Exception $e) {
            Cms_Core::ajaxError(
                'Something went wrong. Please, try again later. ' .
                "\n\nServer said:\n" . $e->getMessage()
            );
        }

        Cms_Core::ajaxSuccess();
    }
}
