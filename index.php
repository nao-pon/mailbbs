<?php
//PHP4.1.0ˆÈ‰º‚Ìê‡
if(phpversion()<"4.1.0"){
    $_GET	= $HTTP_GET_VARS;
    $_POST	= $HTTP_POST_VARS;
    $_SERVER	= $HTTP_SERVER_VARS;
}
define('MAILBBS_REG',true);
include('config.php');

if (isset($_GET['a']) && isset($_GET['p']))
{
	$get_a = $_GET['a'];
	$get_p = $_GET['p'];
	unset($_POST,$_GET);
	$_GET['a'] = $get_a;
	$_GET['p'] = $get_p;
	$def_mode = "admin.php";
}
else
{
	if (isset($_POST['mode'])) $_GET['mode'] = $_POST['mode'];

	if (isset($_GET['mode']))
	{
		if ($_GET['mode'] == "flat") $def_mode = "flat.php";
		elseif ($_GET['mode'] == "list") $def_mode = "list.php";
	}
}
include($def_mode);
?>