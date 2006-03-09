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

$id = (isset($_GET['id']))? $_GET['id'] : "";
	header("Content-type: text/html");
echo "<html><head><title>mailbbs - No.$id Mail header</title></head><body>";
if (file_exists($mailbbs_head_dir.$mailbbs_head_prefix.$id.".cgi")){
	$log = @file($mailbbs_head_dir.$mailbbs_head_prefix.$id.".cgi");
	echo "mailbbs - No.$id Mail header<hr />\n";
	echo "<div style=\"font-size:12px\">\n";
	foreach($log as $line){
		echo nl2br(htmlspecialchars($line));
	}
	echo "</div><hr />";
} else {
	echo "no file !";
}
echo "</body></html>";
?>
