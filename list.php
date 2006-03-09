<?php
// mailbbs.phpのテーブル表示版です。
// malbbs.phpと差し替えてください。
// 画像が添付されたメールのみ表示します。
// 「戻る」がおかしいですが、それほど気にならないかと・・
// GD版でも通常版でも使えると思います

if (!defined('MAILBBS_REG')) exit();

/*------設定------------------*/
include_once('config.php');
//----------------------------

// 振り分け
$user_agent= explode( "/", $_SERVER['HTTP_USER_AGENT']);
switch( $user_agent[0] ){
case "DoCoMo" : include("j.php"); exit;
case "L-mode" : include("j.php"); exit;
case "ASTEL"  : include("j.php"); exit;
case "UP.Browser" : include("ez.php"); exit;
case "PDXGW" :  include("ez.php"); exit;
case "J-PHONE" : include("j.php"); exit;
}
if(preg_match("/^KDDI/",$user_agent[0])){ include("j.php"); exit;}
if(preg_match("/DDIPOCKET/",$user_agent[1])){ include("j.php"); exit;}
?>
<?php

// 右ブロックを表示させない
$show_rblock = 0;

include("../../mainfile.php");
include_once("./version.php");
include(XOOPS_ROOT_PATH."/header.php");
include_once("./version.php");
include_once("./func.php");
include_once("./include/hyp_tickets.php");
include_once("./include/hyp_common_func.php");

HypCommonFunc::str_to_entity($mail);

// ヘルプのみ表示時フッタHTML
$foot = _XOOPS_MAILBBS_VERSION;
$foot = <<<FOOT
<br /><br /><font size=2>- <a href=http://php.s3.to/ target=_blank>レッツPHP!</a> - <a href="http://hypweb.net/xoops/" target="_blank">for XOOPS Ver.$foot</a> -</font>
</div>
FOOT;

$X_admin = 0;
if ( $xoopsUser ) {
	$xoopsModule = XoopsModule::getByDirname("mailbbs");
	if ( $xoopsUser->isAdmin($xoopsModule->mid()) ) { 
		$X_admin = 1;
	}
}

$_POST['pass'] = (isset($_POST['pass']))? $_POST['pass'] : "";
$_GET['mode'] = (isset($_GET['mode']))? $_GET['mode'] : "";
$_GET['page'] = (isset($_GET['page']))? $_GET['page'] : "";

if (isset($_GET['help'])){
	$mailbbs_help = join('',@file('riyou.html'));
	$mailbbs_help = str_replace("_MAIL_",$mail,$mailbbs_help);
	$mailbbs_help = str_replace("_NOSIGN_",$mailbbs_nosign,$mailbbs_help);
	$mailbbs_help = str_replace("_MAXSIZE_",$maxbyte/1000,$mailbbs_help);
	if (!$mailbbs_allowlog) $allow_system = "";
	$mailbbs_help = str_replace("_ALLOW_SYSTEM_",$allow_system,$mailbbs_help);
	
	echo $mailbbs_help;
	
	if (!$mailbbs_help_with){
		echo "<hr /><a href=\"mailto:$mail\">投稿</a> | <a href=\"index.php?mode=flat\">フラット表示</a> | <a href=\"index.php?mode=list\">一覧表示</a><hr />";
		echo $foot;
		include_once(XOOPS_ROOT_PATH."/footer.php");
		exit;
	}
}
?>
<link rel="stylesheet" href="css/default.css" type="text/css" media="screen" charset="shift_jis">
<div style="text-align:center;">
<table class="mailbbs_list_body" cellpadding="0" cellspacing="1">
<tr>

<?php
$lines = mailbbs_log_get();

if (xoops_refcheck())
{
	// 削除処理
	if (isset($_POST['del']))
	{
	  $lines = mailbbs_log_del($lines);
	}
}

// 削除パスフォーム
if ($X_admin)
{
	$pass_tag = ($X_admin)? "<input type=hidden name=pass value=\"$delpass\">" : "削除パス:<br /><input type=password name=pass size=8>";
	$del_tag0 = "<form action={$_SERVER['PHP_SELF']} method=\"POST\">";
	$ticket = $xoopsHypTicket->getTicketHtml( __LINE__ );
  $del_tag = <<<DELFORM
<center>
$ticket
<input type="hidden" name="mode" value="list">
$pass_tag
<input type="submit" value=" 削 除 "></center><br />
DELFORM;
} else {
	$del_tag0 = "";
	$del_tag = "<br /><br />";
}
echo $del_tag0;
$st = (!$_GET['page']) ? 0 : $_GET['page'];
$write_flg = 0;

//添付なしの投稿がある場合スタート位置修正
$view = 0;
if (isset($_GET['pre'])){
	$_st = $st + $page_def_list;
	while($view < $page_def_list){
		$_st --;
		if ($_st <= 0) break;
		list($id, $ptime, $subject, $from, $body, $att,) = explode("<>", $lines[$_st]);
		if ($att) {
			$view++;
		}
	}
	$st = $_st;
}
// IE用改行文字
if (strstr($_SERVER["HTTP_USER_AGENT"], "MSIE"))
	$ie_br = "&#13;&#10;";
else
	$ie_br = "";

// ループ
$view = 0;
for ($i=$st; $view<$page_def_list; $i++) {
  if (!isset($lines[$i])) break;
  // 折り返し
  if ((($view % $return)==0) && $write_flg) echo "</tr><tr>";
  list($id, $ptime, $subject, $from, $body, $att,) = explode("<>", $lines[$i]);

  $imgsrc= "";
  $date = date("Y-m-d G:i", $ptime);
  $size = (int)(@filesize($tmpdir.$att) / 1024 * 10);
  $size = $size / 10;

	$body = str_replace("<br />",$ie_br,$body);
  $body = strip_tags($body);
  $l_body = str_replace("\"","",$body);
  // コメントリンク
  //$link = (trim($body) != "") ? "<a href='#mailbbs_top' title='$body'>□</a>" : "";
  $link = "<a href=\"{$_SERVER['PHP_SELF']}?mode=flat&page={$i}\" id=\"mailbbs_list_link\" title=\"$l_body\">□</a>";
  $del = ($X_admin)? '<input type=checkbox name="del['.$id.']" value=on>' : "";

  // 画像がある時
  if(eregi("\.(gif|jpe?g|png|bmp)$",$att) && file_exists($tmpdir.$att)){
    $href = $tmpdir.rawurlencode($att);
    $size = @GetImageSize($tmpdir.$att);
    $size[0] = (isset($size[0]))? $size[0]:$w;
    $size[1] = (isset($size[1]))? $size[1]:$h;
    $isize = (int)(@filesize($tmpdir.$att) / 1024 * 10);
    $isize = $isize / 10;
		$size_tag = ($size)? "{$ie_br}$size[0]x$size[1]({$isize}KB)" : "{$ie_br}({$isize}KB)";

	  // リサイズ
	  if ($size[0] > $w || $size[1] > $h) {
	    $key_w = $w / $size[0];
	    $key_h = $h / $size[1];
	    ($key_w < $key_h) ? $keys = $key_w : $keys = $key_h;
	    $out_w = $size[0] * $keys;
	    $out_h = $size[1] * $keys;
	    $isresize = 1;
	  } else {
	    $out_w = $size[0];
	    $out_h = $size[1];
	    $isresize = 0;
	  }
    $isize = getimagesize($tmpdir.$att);
    $winw = $isize[0] + 20;
    $winh = $isize[1] + 20;
    // サムネイルがある時、サムネイル表示。
    $filename = preg_replace("/\.[^\.]+$/","",$att);
    // サムネイルがある時、サムネイル表示。
    if (file_exists($thumb_dir.$filename.".jpg")){
			$imgsrc = '<a href="'.$href.'"><img src='.$thumb_dir.rawurlencode($filename).".jpg".' border=0 alt="'.$l_body.$size_tag.'" width='.$out_w.' height='.$out_h.'></a>';
		} else {
			 $imgsrc = '<img src='.$href.' border=0 alt="'.$l_body.'" width='.$out_w.' height='.$out_h.'>';
			 if ($isresize) $imgsrc = "<a href=\"$href\">$imgsrc</a>";

		}
		if (!$write_flg) $imgsrc = "<a name=\"mailbbs\">".$imgsrc."</a>";

  //メイン部分
  echo <<<EOM
      <td class="mailbbs_list_td_body" style="width:{$w}px;">$imgsrc<br />$del<span class="mailbbs_list_title">$subject</span><br />
      <span class="mailbbs_list_date">$date $link</span>
      </td>
EOM;

		$write_flg = 1;
    $view++;
  }
}
// 折り返し
if (($view % $return)==0) echo "</tr><tr>";
$view++;
while (!($view % $return)==0){
	$view++;
	echo "<td></td>";
}
$prev = $st - $page_def_list;
$next = $i;
if ($prev < 0) $prev = 0;
//if ($_GET['mode'] == "admin" || $X_admin) $mode = "&mode=admin";
//ページ
if ($st > 0) {
  $mae = "<a href={$_SERVER['PHP_SELF']}?mode=list&pre&page=$prev#mailbbs id=\"mailbbs_list_link\" title=\"Prev\" style=\"font-size:14px;font-weight:900;\">←</a>";
}
else {
  $mae = "　";
}
if ($next < count($lines)) {
  $tugi = "<a href={$_SERVER['PHP_SELF']}?mode=list&page=$next#mailbbs id=\"mailbbs_list_link\" title=\"Next\" style=\"font-size:14px;font-weight:900;\">→</a>";
}
else {
  $tugi = "　";
}
  //フッタ部分
  echo <<<EOD
    <td class="mailbbs_list_td_navi" style="width:{$w}px;"><br />
    <font style="font-size:18px"><i>写メールBBS</i></font><br />
    <br />
    <span class="mailbbs_list_navi"><a href="index.php?mode=list&help=1" id="mailbbs_list_link">利用方法</a> | <a href="mailto:$mail" id="mailbbs_list_link">投稿</a> | <a href="pop.php?mode=list" id="mailbbs_list_link">更新</a><br />
    <br />
    <a href="index.php?mode=flat" id="mailbbs_list_link">フラット表示</a></span><br />
    <br />
    $del_tag
    <font style="font-size:10px"><a href="http://php.s3.to/" target=_blank id="mailbbs_list_link">レッツPHP!</a></font><br />
    <br />
    $mae&nbsp;&nbsp;&nbsp;$tugi
    </td>
EOD;

if ($X_admin) echo "</form>";
?>
</tr>
</table></div>
<br />
<div style="text-align:center">- <a href="http://hypweb.net/xoops/" target="_blank">for XOOPS Ver.<?php echo _XOOPS_MAILBBS_VERSION ?></a> -</div>
<?php
//規定時間以上経っていたら<img>タグでPOP更新
if (!$st) { // 表示ページが最初のページなら
	if (filemtime($log) < time() - $mailbbs_limit_min * 60) {
		echo "<div style=\"text-align:center;\"><img src=\"pop.php?img=1&time=".time()."\" width=70 height=16 /></div>";
	}
}
include_once(XOOPS_ROOT_PATH."/footer.php");
?>
