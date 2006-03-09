<?php

include('../../mainfile.php');

$xoopsMailer =& getMailer();

global $xoopsConfig;

$xoopsMailer->useMail();
$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
$xoopsMailer->setFromName($xoopsConfig['sitename']);
$xoopsMailer->setSubject("テスト");
$xoopsMailer->setBody("テスト");
$xoopsMailer->setToEmails($xoopsConfig['adminmail']);
$xoopsMailer->send();
$xoopsMailer->reset();


?>