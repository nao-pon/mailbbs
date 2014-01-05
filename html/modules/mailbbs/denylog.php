<?php
include("../../mainfile.php");
include_once(XOOPS_ROOT_PATH."/class/xoopsmodule.php");
if ( $xoopsUser ) {
	$xoopsModule = XoopsModule::getByDirname("mailbbs");
	if ( !$xoopsUser->isAdmin($xoopsModule->mid()) ) { 
		redirect_header(XOOPS_URL."/",3,_NOPERM);
		exit();
	}
} else {
	redirect_header(XOOPS_URL."/",3,_NOPERM);
	exit();
}

include("./config.php");

	header("Content-type: text/html; charset=EUC-JP");
echo "<html>
	<head>
		<meta http-equiv='Content-Type' content='text/html; charset=EUC-JP' />
		<title>写メールBBS for XOOPS - 登録拒否メール</title>
	</head>
	<body>";
if (file_exists($mailbbs_denylog)){
	$log = @file($mailbbs_denylog);
	echo "写メールBBS for XOOPS - 登録拒否メール<hr />\n";
	echo "<div style=\"font-size:12px\">\n";
	foreach($log as $line){
		echo nl2br(htmlspecialchars($line, ENT_COMPAT, _CHARSET));
	}
	echo "</div><hr />";
} else {
	echo "no file !";
}
echo "</body></html>";
?>
