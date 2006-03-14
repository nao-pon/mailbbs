<?php
/*
 * Created on 2006/03/14
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
include("../../mainfile.php");
include("./config.php");
include_once("./version.php");
include_once("./func.php");
//include_once("./include/hyp_tickets.php");
include_once("./include/hyp_common_func.php");

$X_admin = 0;
if ( is_object($xoopsUser) )
{
	$xoopsModule = XoopsModule::getByDirname("mailbbs");
	if ( $xoopsUser->isAdmin($xoopsModule->mid()) )
	{
		$X_admin = 1;
	}
}

$id = (isset($_GET['id']))? (int)$_GET['id'] : 0;
$rc = (isset($_GET['rc']))? (int)$_GET['rc'] : 0;
$rf = (isset($_GET['rf']))? $_GET['rf'] : "";

if (!$X_admin || !$id || !$rc) done($rf);

$lines = mailbbs_log_get();

foreach ($lines as $line)
{
	list($tid, $ptime, $subject, $from, $body, $att, $comments, $allow) = array_pad(explode("<>", trim($line)),8,"");
	//echo "$tid<br>";
	if ($tid != $id) continue;
	//echo "att:$att";
	break;
}

if ($att)
{
	HypCommonFunc::rotateImage($tmpdir.$att, $rc);
	HypCommonFunc::rotateImage($thumb_dir.$att, $rc);
}
done($rf);

function done($rf)
{
	if (!$rf) exit();
	header("Location: ".preg_replace("#^(https?://[^/]+).*$#","$1",XOOPS_URL).$rf);
}
?>
