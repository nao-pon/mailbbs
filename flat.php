<?php
/*----------------------------*/
//include_once('config.php');
/*----------------------------*/
if (!defined('MAILBBS_REG')) exit();

include("../../mainfile.php");

// 振り分け
if (! defined('HYP_K_TAI_RENDER') || ! HYP_K_TAI_RENDER) {
	$user_agent= explode( "/", $_SERVER['HTTP_USER_AGENT']);
	switch( $user_agent[0] ){
		case "DoCoMo" :
		case "L-mode" :
		case "ASTEL"  :
		case "UP.Browser" :
		case "PDXGW" :
		case "J-PHONE" :
		case "Vodafone" :
		case "SoftBank" :
			// clear output buffer
			while( ob_get_level() ) {
				ob_end_clean() ;
			}
			include("j.php");
			exit;
	}
	if(preg_match("/^KDDI/",$user_agent[0]) || preg_match("/DDIPOCKET/",$user_agent[1])) {
		// clear output buffer
		while( ob_get_level() ) {
			ob_end_clean() ;
		}
		include("j.php");
		exit;
	}
}

include_once("./version.php");
include_once("./func.php");
include_once("./include/hyp_tickets.php");
include_once("./include/hyp_common/hyp_common_func.php");
//error_reporting(E_ALL);

HypCommonFunc::str_to_entity($mail);

$wiki_helper_js_top = '';
if ($use_wiki_helper === 1 && file_exists(XOOPS_ROOT_PATH."/modules/pukiwiki/skin/default.ja.js"))
{
	// Wikiヘルパー
	// for PukiWiki helper.
	$url = XOOPS_URL."/modules/pukiwiki";
	$wiki_helper_js = <<<EOD
<script type="text/javascript">
<!--
var pukiwiki_root_url = "{$url}/";
-->
</script>
<script type="text/javascript" src="{$url}/skin/default.ja.js"></script>
<script type="text/javascript">
<!--
	if (pukiwiki_WinIE || pukiwiki_Gecko)
	{
		document.write('<div>');
		pukiwiki_show_fontset_img();
		document.write('<'+'/'+'div>');
	}
-->
</script>
EOD;
} else if (! isset($GLOBALS['hyp_preload_head_tag']) && $use_wiki_helper && file_exists(XOOPS_ROOT_PATH.'/modules/'.$use_wiki_helper.'/skin/loader.php')) {
	$wiki_helper_js = '';
	$wiki_helper_js_top = '<script type="text/javascript" src="'.XOOPS_URL.'/modules/'.$use_wiki_helper.'/skin/loader.php?src=default.ja.js"></script>';
} else {
	$wiki_helper_js = "";
	$use_wiki_helper = 0;
}

// フッタHTML
$foot = _XOOPS_MAILBBS_VERSION;
$foot = <<<FOOT
<br /><br /><font size=2>- <a href=http://php.s3.to/ target=_blank>レッツPHP!</a> - <a href="http://hypweb.net/xoops/" target="_blank">for XOOPS Ver.$foot</a> -</font>
</div>
FOOT;

$X_admin = 0;
if ( is_object($xoopsUser) )
{
	$xoopsModule = XoopsModule::getByDirname("mailbbs");
	if ( $xoopsUser->isAdmin($xoopsModule->mid()) )
	{
		$X_admin = 1;
	}
	$X_uname = $xoopsUser->uname();
}
else
{
	$X_uname = (!empty($_COOKIE["mailbbs_un"]))? $_COOKIE["mailbbs_un"] : $mailbbs_nosign;
}
$X_uname = htmlspecialchars($X_uname);

$_GET['page'] = (isset($_GET['page']))? $_GET['page'] : 0;
$mailbbs_denylink = ($X_admin)? " | <a href=\"denylog.php\" target=\"mailbbs\">登録拒否メール</a>" : "";

$mailbbs_body = <<<_HTML_
<link rel="stylesheet" href="css/default.css" type="text/css" media="screen" charset="shift_jis">
<div style="text-align:center;width:100%;">
<div class="mailbbs_flat_title">写メール　BBS</div>
_POP_IMG_
_HTML_;

if (isset($_GET['help'])){
	$mailbbs_help = join('',@file('riyou.html'));
	$mailbbs_help = str_replace("_MAIL_",$mail,$mailbbs_help);
	$mailbbs_help = str_replace("_NOSIGN_",$mailbbs_nosign,$mailbbs_help);
	$mailbbs_help = str_replace("_MAXSIZE_",$maxbyte/1000,$mailbbs_help);
	if (!$mailbbs_allowlog) $allow_system = "";
	$mailbbs_help = str_replace("_ALLOW_SYSTEM_",$allow_system,$mailbbs_help);
	$mailbbs_body .= $mailbbs_help;

	if (!$mailbbs_help_with){
		$mailbbs_body .= "<hr /><a href=\"mailto:$mail\">投稿</a> | <a href=\"index.php?mode=flat\">フラット表示</a> | <a href=\"index.php?mode=list\">一覧表示</a><hr />";
		$mailbbs_body .= $foot;

		if (filemtime($log) < time() - $mailbbs_limit_min * 60) {
			$mailbbs_body = str_replace("_POP_IMG_","<div style=\"text-align:center;\"><img src=\"pop.php?img=1&time=".time()."\" width=70 height=16 /></div>",$mailbbs_body);
		} else {
			$mailbbs_body = str_replace("_POP_IMG_","",$mailbbs_body);
		}
		include(XOOPS_ROOT_PATH."/header.php");
		echo $mailbbs_body;
		include_once(XOOPS_ROOT_PATH."/footer.php");
		exit;
	}

}
if (!isset($_GET['help'])) {
	$mailbbs_body .= "<a href=\"index.php?mode=flat&help=1\">利用方法</a> | ";
}
$mailbbs_body .= <<<_HTML_
<a href="mailto:$mail">投稿</a> | <a href="pop.php?mode=flat">更新</a> | <a href="index.php?mode=list">一覧表示</a>
$mailbbs_denylink
<br /><br />
_HTML_;

$lines = mailbbs_log_get();

if (xoops_refcheck())
{
	// コメント挿入処理
	if (isset($_POST['comment']) && mailbbs_check_array($_POST['comment']))
	{
		$lines = mailbbs_log_comment($lines);
	}
	else
	{
		// コメント削除処理
		if (isset($_POST['del_com']))
		{
			$lines = mailbbs_log_comment_del($lines);
		}

		// 記事削除処理
		if (isset($_POST['del']))
		{
			$lines = mailbbs_log_del($lines);
		}

		// 記事承認処理
		if ($X_admin && isset($_POST['allow']))
		{
			$lines = mailbbs_log_allow($lines);
		}
	}
}

// 削除パスフォーム
//if ($_GET['mode'] == "admin") {
$pass_tag = ($X_admin)? "" : "投稿時メアド：<input type=password name=pass size=25>";
$ticket = $xoopsHypTicket->getTicketHtml( __LINE__ );
$mailbbs_body .= <<<DELFORM
<form action="{$_SERVER['PHP_SELF']}" method=POST id="mainform" name="mainform">
$ticket
<input type="hidden" name="mode" value="flat">
$pass_tag
<input type="submit" name="b_del" value="チェック処理" /><br />
DELFORM;
//}

$st = (!isset($_GET['page'])) ? 0 : $_GET['page'];
$tgt_found = $tgt_id = 0;
if (isset($_GET['id'])) {
	if ($_GET['id'] != 0){
		$tgt_id = $_GET['id'];
		$st = 0;
		$_page_def_flat = $page_def_flat;
		$page_def_flat = count($lines);
	}
}

// IE用改行文字
if (strstr($_SERVER["HTTP_USER_AGENT"], "MSIE"))
	$ie_br = "&#13;&#10;";
else
	$ie_br = "";

// XOOPSサニタイザ
$myts =& MyTextSanitizer::getInstance();

$title = "";
$rt_url = rawurlencode($_SERVER['REQUEST_URI']);
// ループ
for ($i=$st; $i<$st+$page_def_flat; $i++)
{
	$imgsrc = $body = $subject = "";
	if (!isset($lines[$i]) || $tgt_found) break;
	list($id, $ptime, $subject, $from, $body, $att, $comments, $allow) = array_pad(explode("<>", trim($lines[$i])),8,"");
	if ($tgt_id) {
		if ($tgt_id != $id) continue;
		$tgt_found = 1;
		$_st = $i;
		$title = $subject."-";
	}

	if ($comments)
	{
		$c_array = explode("</>",$comments);
		$comments = "<hr />";
		$count = 0;
		foreach($c_array as $comment)
		{
			if ($comment)
			{
				$time = substr($comment,-10);
				$comment = substr($comment,0,strlen($comment)-10);
				list($name,$comment) = explode("\t",$comment);
				//echo $myts->displayTarea($name);
				if ($use_wiki_helper === 0 || $use_wiki_helper === 1) {
					$comments .= "<input type=checkbox name=\"del_com[$id][$count]\" value=\"on\" title=\"削除チェック\"><strong>".preg_replace("/(^<p>|(<br \/>\n)?<\/p>$)/","",$myts->displayTarea($name))."</strong>: ".preg_replace("/(^<p>|(<br \/>\n)?<\/p>$)/","",$myts->displayTarea($comment))." - <span class=\"mailbbs_flat_comment_date\">".date('y/m/d G:i',$time)."</span><br />";
				} else {
					$_text = '\'\'' . $name . '\'\': ' . $comment . ' - ' . '&font(class:mailbbs_flat_comment_date){'.date('y/m/d G:i',$time).'};';
					$comments .= "<input type=checkbox name=\"del_com[$id][$count]\" value=\"on\" title=\"削除チェック\">" . $myts->displayTarea($_text);
				}
			}
			$count++;
		}
	}

	$date = date("y/m/d G:i", $ptime);
	$size = (int)(@filesize($tmpdir.$att) / 1024 * 10);
	$size = $size / 10;

	// 本文E-Mailをリンク
	// mb系関数が使える場合は以下2行と置き換え。それ以外は文字化けするかも
	//mb_regex_encoding("SJIS");
	//$body = mb_eregi_replace("([-a-z0-9_.]+@[-a-z0-9_.]+)", "<a href='mailto:\\1'>\\1</a>", $body);
	//$body = eregi_replace("([-a-z0-9_.]+@[-a-z0-9_.]+)", "<a href='mailto:\\1'>\\1</a>", $body);
	// URLリンク
	//$body = ereg_replace("(https?|ftp)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)","<a href=\"\\1\\2\" target=_top>\\1\\2</a>",$body);

	// 記事リンク
	$link_url = XOOPS_URL."/modules/mailbbs/index.php?flat&id=";
	$body = preg_replace("/&gt;(?:&gt;)?\s*(\d+)/","[url={$link_url}$1][b]$0[/b][/url]",$body);

	// XOOPS でサニタイズ
	$body = $myts->displayTarea(str_replace(array("&lt;","&gt;","<br />"),array("<",">","\n"),$body));

	$rotate_link = "";
	$rotate_onclick = " onClick=\"return(confirm('イメージを回転しますか?'));\"";
	// 画像がある時IMGタグ
	if(eregi("\.(gif|jpe?g|png|bmp)$",$att))
	{
		if ($X_admin)
		{
			$rotate_link = "[<a href=\"rotate.php?id=$id&amp;rc=3&amp;rf={$rt_url}\" title=\"イメージ左回転\"{$rotate_onclick}>L</a>][<a href=\"rotate.php?id=$id&amp;rc=1&amp;rf={$rt_url}\" title=\"イメージ右回転\"{$rotate_onclick}>R</a>] ";
		}
		$href = $tmpdir.rawurlencode($att);
		$psize = @GetImageSize($tmpdir.$att);

		$size_tag = preg_replace("/^[0-9]+-/","",$att).$ie_br;
		$size_tag .= ($psize)? "$psize[0]x$psize[1]" : "";

		// サムネイルがある時、サムネイル表示。
		$filename = preg_replace("/\.[^\.]+$/","",$att);
		if (file_exists($thumb_dir.$filename.".jpg"))
		{
			$isize = @GetImageSize($thumb_dir.$filename.".jpg");
			$imgsrc = "<a href=\"$href\"><img src=\"$thumb_dir".rawurlencode($filename).".jpg\" class=\"mailbbs_flat_img\" alt=\"$size_tag({$size}KB)\" $isize[3]></a>";
		}
		else
		{
			$psize_tag = "width=\"$w\"";
			if ($psize){
				// リサイズ
				if ($psize[0] > $w || $psize[1] > $h) {
					$key_w = $w / $psize[0];
					$key_h = $h / $psize[1];
					($key_w < $key_h) ? $keys = $key_w : $keys = $key_h;
					$out_w = (int)$psize[0] * $keys;
					$out_h = (int)$psize[1] * $keys;
					$isresize = 1;
				} else {
					$out_w = $psize[0];
					$out_h = $psize[1];
					$isresize = 0;
				}
				$psize_tag = "width=\"$out_w\" height=\"$out_h\"";
			}
			$imgsrc = "<img src=\"$href\" class=\"mailbbs_flat_img\" alt=\"$size_tag({$size}KB)\" $psize_tag>";
			if ($isresize) $imgsrc = "<a href=\"$href\">$imgsrc</a>";
		}

	}//それ以外はリンク
	elseif(trim($att)!="")
	{
		$body.= '<br />添付：<a href='.$tmpdir.rawurlencode($att).'>'.$att.'</a>('.$size.'KB)';
	}

	$del = ' <input type=checkbox name="del['.$id.']" value="on">:削除';

	// ヘッダ情報リンクタグ
	if ($X_admin && (file_exists($mailbbs_head_dir.$mailbbs_head_prefix.$id.".cgi")))
	{
		$header_link = " <a href=\"head_show.php?id=$id\" target=\"mailbbs\" title=\"ヘッダ情報表示\"><img src=\"option.gif\" alt=\"ヘッダ情報表示\" align=\"bottom\" /></a>";
	}
	else
	{
		$header_link = "";
	}

	// 承認用タグ
	if ($X_admin && $allow)
	{
		$allow_tag = ' <input type=checkbox name="allow['.$id.']" value="on">:承認';
	}
	else
		$allow_tag = "";

	//コメント用
	$comment = <<<EOM
<hr />
$wiki_helper_js
Name: <input type="text" name="name[$id]" size="10" value="$X_uname" />
<input type="text" name="comment[$id]" size="36" rel="wikihelper" />
<input type="hidden" name="enchint" size="ぷ" />
<input type="submit" name="b_comment[$id]" value="つっこみ" />
EOM;

	//メイン表示
	$mailbbs_body .= <<<EOM
$wiki_helper_js_top
<table cellspacing=1 class="mailbbs_flat_table">
	<tr><td class="mailbbs_flat_td_title"><span class="mailbbs_flat_span_title">No.$id <b>$subject</b></span></td><td class="mailbbs_flat_td_title" style="text-align:right;"><span class="mailbbs_flat_span_title">{$rotate_link}{$header_link}{$allow_tag}{$del}</span></td></tr>
	<tr><td class="mailbbs_flat_td_body" colspan="2">
		<div class="mailbbs_flat_body_text">
		$imgsrc $body
		<br clear=all />
		<div class="mailbbs_flat_body_date">$date</div>
		$comments
		$comment
		</div>
	</td></tr>
</table>
EOM;
}

if ($tgt_found)
{
	$_GET['page'] = $st = $_st;
	$prev = $st - $_page_def_flat;
	if ($prev < 0) $prev = 0;
	$next = $st + 1;
}
else
{
	if ($tgt_id)
	{
		$ret = (!empty($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF'];
		redirect_header($ret,1,"指定された記事が見つかりません。");
		exit;
	}
	$prev = $st - $page_def_flat;
	if ($prev < 0) $prev = 0;
	$next = $st + $page_def_flat;
}
//ページ
if ($_GET['page'])
{
	$mailbbs_body .= "<a href=\"{$_SERVER['PHP_SELF']}?mode=flat&page=$prev\">←PREV</a>&nbsp;&nbsp;";
}
if ($next < count($lines))
{
	$mailbbs_body .= "<a href=\"{$_SERVER['PHP_SELF']}?mode=flat&page=$next\">NEXT→</a>";
}
$mailbbs_body .= "</form>";
$mailbbs_body .= $foot;
//規定時間以上経っていたら<img>タグでPOP更新
if (!$st)
{ // 表示ページが最初のページなら
	if (filemtime($log) < time() - $mailbbs_limit_min * 60)
	{
		$mailbbs_body = str_replace("_POP_IMG_","<div style=\"text-align:center;\"><img src=\"pop.php?img=1&time=".time()."\" width=70 height=16 /></div>",$mailbbs_body);
	}
	else
	{
		$mailbbs_body = str_replace("_POP_IMG_","<br />",$mailbbs_body);
	}
}
else
{
	$mailbbs_body = str_replace("_POP_IMG_","<br />",$mailbbs_body);
}



include(XOOPS_ROOT_PATH."/header.php");

if (is_object($xoopsTpl)){
	// <title>にページ名をプラス
	$xoops_pagetitle = $xoopsModule->name();
	$xoops_pagetitle = $title.$xoops_pagetitle;
	$xoopsTpl->assign("xoops_pagetitle",$xoops_pagetitle);
}

echo $mailbbs_body;
include_once(XOOPS_ROOT_PATH."/footer.php");
?>
