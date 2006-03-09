<?php
/*
 * Created on 2006/03/09
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

// 管理画面用のヘッダファイル読み込み

require_once('../../../include/cp_header.php');

//(DB update section)

// beggining of Output
xoops_cp_header();
include( './mymenu.php' ) ;

// check $xoopsModule
if( ! is_object( $xoopsModule ) ) redirect_header( XOOPS_URL.'/user.php' , 1 , _NOPERM ) ;

// title
echo "<h3 style='text-align:left;'>".$xoopsModule->name()."</h3>\n" ;


xoops_cp_footer();
?>
