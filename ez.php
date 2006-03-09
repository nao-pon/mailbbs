<?php
// 不正アクセス
if (!defined("MAILBBS_REG")) {
	$jump = "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
	$jump = preg_replace("/[^\/]+$/","",$jump);
	header("Location: $jump");
}

header("Content-Type: text/x-hdml;charset=Shift_JIS\n\n");
/*----------------------------*/
include_once('config.php');
/*----------------------------*/
?>
<HDML version=3.0 public=true>
<DISPLAY NAME="bbs" TITLE="写メールBBS">
<center>写メール　BBS<br>
<br>
<a task="go" dest="riyou_sjis.html">利用方法</a>|<a task="go" dest="pop.php">更新</a>
<br>---------------<br>

<?php
$lines = mailbbs_log_get();

$st = (empty($_GET['page'])) ? 0 : $_GET['page'];

for ($i=$st; $i<$st+$page_def_j; $i++) {
  if ($lines[$i] == "") break;
  $imgsrc = $body = $subject = "";
  list($id, $ptime, $subject, $from, $body, $att,) = explode("<>", $lines[$i]);
  $body = str_replace("<br />","<br>",$body);
  $date = gmdate("y/m/d G:i", $ptime+9*3600);
  $filename = substr($att, 0, -4);
    if (file_exists($thumb_dir.$filename.".jpg")) {
    $jsize = (int)(@filesize($thumb_dir.$filename.".jpg") / 1024);
    $psize = (int)(@filesize($thumb_dir.$filename.".png") / 1024);
    $imgsrc = '<br><a task="go" dest="'.$thumb_dir.rawurlencode($filename).'.jpg">jpg</a>('.$jsize.'KB)';
    //$imgsrc.= ',<a task="go" dest="'.$thumb_dir.rawurlencode($filename).'.png">png</a>('.$psize.'KB)';
  }else{
    $size = (int)(@filesize($tmpdir.$att) / 1024);
    $ext = substr($att,-3);
    $imgsrc = ($att!="") ? '<br><a task="go" dest="'.$tmpdir.rawurlencode($att).'">'.$ext.'</a>('.$size.'KB)' : '';
  }
  $output = <<<EOM
$subject<right>$date<br>
$body $imgsrc 
<br>------------<br>
EOM;
	echo convert($output);
}
$prev = $st - $page_def_j;
$next = $st + $page_def_j;

if (!empty($_GET['page'])) {
  echo "<a task=\"go\" dest=\"{$_SERVER['PHP_SELF']}?page=$prev\">←PREV</a>　";
}
if ($next < count($lines)) {
  echo "<a task=\"go\" dest=\"{$_SERVER['PHP_SELF']}?page=$next\">NEXT→</a> ";
}
?>
</DISPLAY></HDML>
<?php
/* 文字コードコンバートauto→SJIS */
function convert($str) {
  if (function_exists('mb_convert_encoding')) {
    return mb_convert_encoding($str, "SJIS", "auto");
  } elseif (function_exists('JcodeConvert')) {
    return JcodeConvert($str, 0, 2);
  }
  return true;
}
?>