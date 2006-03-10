<?php
$modversion['name'] = '写メールBBS';
$modversion['description'] = '写メールBBS';
$modversion['credits'] = "";
$modversion['author'] = "";
$modversion['help'] = "xoopsmembers.html";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = "no";
$modversion['image'] = "logo.gif";
$modversion['dirname'] = "mailbbs";
include(XOOPS_ROOT_PATH."/modules/".$modversion['dirname']."/version.php");
$modversion['version'] = _XOOPS_MAILBBS_VERSION;

// Admin things
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/index.php";
$modversion['adminmenu'] = "admin/menu.php";

// Blocks
$modversion['blocks'][1]['file'] = "newmail.php";
$modversion['blocks'][1]['name'] = '写メールBBS(ランダム)';
$modversion['blocks'][1]['description'] = "MailBBS";
$modversion['blocks'][1]['show_func'] = "b_mailbbs_show";
$modversion['blocks'][1]['edit_func'] = "b_mailbbs_edit";
$modversion['blocks'][1]['options'] = "1";
$modversion['blocks'][1]['can_clone'] = true ;

$modversion['blocks'][2]['file'] = "newmail.php";
$modversion['blocks'][2]['name'] = '写メールBBS(最新)';
$modversion['blocks'][2]['description'] = "MailBBS(New)";
$modversion['blocks'][2]['show_func'] = "b_mailbbs_new";
$modversion['blocks'][2]['edit_func'] = "b_mailbbs_edit";
$modversion['blocks'][2]['options'] = "1";
$modversion['blocks'][2]['can_clone'] = true ;

// Menu
$modversion['hasMain'] = 1;
$modversion['sub'][1]['name'] = "利用方法";
$modversion['sub'][1]['url'] = "index.php?help=1";
$modversion['sub'][2]['name'] = "フラット表示";
$modversion['sub'][2]['url'] = "index.php?mode=flat";
$modversion['sub'][3]['name'] = "一覧表示";
$modversion['sub'][3]['url'] = "index.php?mode=list";
$modversion['sub'][4]['name'] = "更新";
$modversion['sub'][4]['url'] = "pop.php";

// Search
$modversion['hasSearch'] = 1;
$modversion['search']['file'] = "search.inc.php";
$modversion['search']['func'] = "mailbbs_search";

// On Update
if( ! empty( $_POST['fct'] ) && ! empty( $_POST['op'] ) && $_POST['fct'] == 'modulesadmin' && $_POST['op'] == 'update_ok' && $_POST['dirname'] == $modversion['dirname'] ) {
	include dirname( __FILE__ ) . "/include/onupdate.inc.php" ;
}
?>