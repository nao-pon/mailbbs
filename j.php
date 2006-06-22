<?php

// 不正アクセス
if (!defined("MAILBBS_REG")) {
	$jump = "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
	$jump = preg_replace("/[^\/]+$/","",$jump);
	header("Location: $jump");
}

//mb_string ini set by nao-pon

/*
ini_set("output_buffering","Off");
ini_set("default_charset","EUC-JP");
ini_set("mbstring.language","Japanese");
ini_set("mbstring.encoding_translation","On");
ini_set("mbstring.http_input","Auto");
ini_set("mbstring.http_output","SJIS");
ini_set("mbstring.internal_encoding","EUC-JP");
ini_set("mbstring.substitute_character","none");
*/

/*----------------------------*/
include("../../mainfile.php");
include_once('config.php');
include_once('func.php');
include_once("./include/hyp_tickets.php");
include_once("./include/hyp_common/hyp_common_func.php");
/*----------------------------*/

//error_reporting(E_ALL);

//$hypcf = new HypCommonFunction();
//$hypcf->str_to_entity($mail);

if (!isset($_GET['help'])){
$output = <<<EOM
<html><head>
<meta http-equiv="content-type" content="text/html; charset=Shift_JIS">
<title>写メール　BBS</title>
</head>
<body bgcolor="#ffffff" text="#333333" link="#666666" vlink="#999999" alink="#ffcc00">
<center>写メール　BBS<br><br>
<a href="?help">ヘルプ</a>|<a href="pop.php">更新(Top)</a><br>
投稿先メアド<br><a href="mailto:{$mail}">{$mail}</a>
<br></center><hr>
EOM;
$lines = mailbbs_log_get();

// コメント挿入処理
if (isset($_POST['comment']) && mailbbs_check_array($_POST['comment']))
{
	$lines = mailbbs_log_comment($lines,1);
}

$st = (empty($_GET['page'])) ? 0 : $_GET['page'];
$tgt_found = $tgt_id = 0;
if (isset($_GET['id'])) {
	if ($_GET['id'] != 0){
		$tgt_id = $_GET['id'];
		$st = 0;
		$_page_def_j = $page_def_j;
		$page_def_j = count($lines);
	}
}

// XOOPSサニタイザ
$myts =& MyTextSanitizer::getInstance();

for ($i=$st; $i<$st+$page_def_j; $i++) {
	if ($lines[$i] == "") break;
	$c_form = $imgsrc = $body = $subject = "";
	list($id, $ptime, $subject, $from, $body, $att, $comments) = explode("<>", trim($lines[$i]));
	
	if ($tgt_id) {
		if ($tgt_id != $id) continue;
		$tgt_found = 1;
		$_st = $i;
	}
	
	$c_count = count(explode("</>",$comments)) - 1;
	$c_tag = "<a href=\"{$_SERVER['PHP_SELF']}?id=$id\">つっこみ[{$c_count}]</a>";

	if ($tgt_id)
	{
		$c_tag="";
		if ($comments)
		{
			$c_array = explode("</>",$comments);
			$c_tag = "<hr />";
			$count = 0;
			foreach($c_array as $comment)
			{
				if ($comment)
				{
					$time = substr($comment,-10);
					$comment = substr($comment,0,strlen($comment)-10);
					list($name,$comment) = explode("\t",$comment);
					
					// XOOPS でサニタイズ
					$comment = $myts->displayTarea(str_replace(array("&lt;","&gt;","<br />"),array("<",">","\n"),$comment));
					$comment = str_replace("\n","",$comment);
					$comment = str_replace(array("<br />","<p>","</p>"),"\n",$comment);
					$comment = nl2br(trim(preg_replace("/\n+/","\n",strip_tags($comment))));
					
					$c_tag .= strip_tags($myts->displayTarea($name)).":<br>".$comment."<br>".date('y/m/d G:i',$time)."<hr>";
				}
			}
			$c_tag = "</center>".$c_tag."<center>";
		}
		else
			$c_tag = "<hr>";
		$ticket = $xoopsHypTicket->getTicketHtml( __LINE__ );
		$c_form = <<<EOM
<form action="{$_SERVER['PHP_SELF']}" method="POST">
$ticket
Name:<input type="text" name="name[{$tgt_id}]" size=10><br>
<textarea name="comment[{$tgt_id}]"></textarea><br>
<input type="submit" name="b_comment[{$tgt_id}]" value="つっこみ">
</form>
EOM;
	}
	
	$date = gmdate("y/m/d G:i", $ptime+9*3600);
	$filename = substr($att, 0, -4);
	$imgsrc = '<br>';
	if (file_exists($thumb_dir.$filename.".jpg"))
	{
		$jsize = (int)(@filesize($thumb_dir.$filename.".jpg") / 1024);
		//$psize = (int)(@filesize($thumb_dir.$filename.".png") / 1024);
		$imgsrc = '<a href='.$thumb_dir.rawurlencode($filename).'.jpg>jpg</a>('.$jsize.'K)';
		//$imgsrc.= ',<a href='.$thumb_dir.rawurlencode($filename).'.png>png</a>('.$psize.'K)';
	}
	$size = (int)(@filesize($tmpdir.$att) / 1024);
	$ext = substr($att,-3);
	$imgsrc .= ($att!="") ? '|<a href='.$tmpdir.rawurlencode($att).'>'.$ext.'</a>('.$size.'K)' : '';
	//$body = eregi_replace("([-a-z0-9_.]+@[-a-z0-9_.]+)", "<a href='mailto:\\1'>\\1</a>", $body);
	
	// 記事リンク
	$body = preg_replace("/&gt;(?:&gt;)?\s*(\d+)/","LINK[$1]",$body);
	
	// XOOPS でサニタイズ
	$body = $myts->displayTarea(str_replace(array("&lt;","&gt;","<br />"),array("<",">","\n"),$body));
	$body = str_replace("\n","",$body);
	$body = str_replace(array("<br />","<p>","</p>"),"\n",$body);
	$body = nl2br(trim(preg_replace("/\n{3,}/","\n\n",strip_tags($body))));
	
	// 記事リンク
	$body = preg_replace("/LINK\[(\d+)\]/","<a href=\"?id=$1\">&gt;$1</a>",$body);

	$output .= <<<EOM
[$id]$subject
<p>$body</p>
<center>
$imgsrc<br>
$date<br>
{$c_tag}
{$c_form}
</center>
<hr>
EOM;
}

if ($tgt_found)
{
	$_GET['page'] = $st = $_st;
	$prev = $st - $_page_def_j;
	if ($prev < 0) $prev = 0;
	$next = $st + 1;
}
else
{
	$prev = $st - $page_def_j;
	if ($prev < 0) $prev = 0;
	$next = $st + $page_def_j;
}
//$prev = $st - $page_def_j;
//$next = $st + $page_def_j;
$output .= "<center>";
if (!empty($_GET['page'])) $output .= "<a href={$_SERVER['PHP_SELF']}?page=$prev>←PREV</a>　";
if ($next < count($lines)) $output .= "<a href={$_SERVER['PHP_SELF']}?page=$next>NEXT→</a>";
$output .= "</center>";
$output .= "</body></html>";
} else {
	$mailbbs_help = join('',@file('riyou.html'));
	$mailbbs_help = str_replace("_MAIL_",$mail,$mailbbs_help);
	$mailbbs_help = str_replace("_NOSIGN_",$mailbbs_nosign,$mailbbs_help);
	$mailbbs_help = str_replace("_MAXSIZE_",$maxbyte/1000,$mailbbs_help);
	if (!$mailbbs_allowlog) $allow_system = "";
	$mailbbs_help = str_replace("_ALLOW_SYSTEM_",$allow_system,$mailbbs_help);

	$output = <<<EOM
<html><head>
<meta http-equiv="content-type" content="text/html; charset=Shift_JIS">
<title>写メール　BBS</title>
</head>
<body bgcolor="#ffffff" text="#333333" link="#666666" vlink="#999999" alink="#ffcc00">
$mailbbs_help
</body></html>
EOM;
}
header("Content-type: text/html; charset=shift_jis");
echo convert(str_replace(array("<br />","\r","\n"),array("<br>",""),$output));
exit();
/* 文字コードコンバートauto→SJIS */
function convert($str) {
	if (function_exists('mb_convert_encoding'))
	{
		return mb_convert_encoding($str, "SJIS", "auto");
	}
	elseif (function_exists('JcodeConvert'))
	{
		return JcodeConvert($str, 0, 2);
	}
	return true;
}
?>