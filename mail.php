<?php

include('../../mainfile.php');

$xoopsMailer =& getMailer();

global $xoopsConfig;

$xoopsMailer->useMail();
$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
$xoopsMailer->setFromName($xoopsConfig['sitename']);
$xoopsMailer->setSubject("�e�X�g");
$xoopsMailer->setBody("�e�X�g");
$xoopsMailer->setToEmails($xoopsConfig['adminmail']);
$xoopsMailer->send();
$xoopsMailer->reset();


?>