<?php
// admin.php for 写メールBBS XOOPS by nao-pon
// at 2005/01/15

error_reporting(E_ALL);

if (!defined('MAILBBS_REG') || !empty($_SERVER['HTTP_REFERER'])) output("ERROR: 不正アクセス");

if (!isset($_GET['a']) || !isset($_GET['p'])) output("ERROR: 無効なリンクです");

include("./func.php");
include_once("./include/hyp_tickets.php");

// 管理者モード
$X_admin = 1;

// データ読み込み
$lines = mailbbs_log_get();

$found = 0;
$action = "";
foreach($lines as $line)
{
	$data = array_pad(explode("<>",rtrim($line)),8,"");
	
	// 処理対象にマッチ
	if ($_GET['p'] === md5($data[0].$data[1].$host.$user.$pass))
	{
		// 承認モード
		if ($_GET['a'] == "a")
		{
			$_POST['allow'][$data[0]] = "on";
			if (!$data[7])
			{
				$found = 1;
				$action = "は承認済みです。";
				$num = $data[0];
				break;
			}
			if (mailbbs_log_allow($lines,TRUE))
			{
				$found = 1;
				$action = "を承認しました。";
				$num = $data[0];
				break;
			}
		}
		
		// 削除モード
		else if ($_GET['a'] == "d")
		{
			$_POST['del'][$data[0]] = "on";
			if (mailbbs_log_del($lines,TRUE))
			{
				$found = 1;
				$action = "を削除しました。";
				$num = $data[0];
				break;
			}
		}
	}
}
if ($found)
{
	
	output("記事ID.{$num}{$action}");
}
else
	output("指定された記事が見つかりません。");


function output($str)
{
	$out = <<<_EOD
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=shift_jis">
<title>$str</title>
</head>
<body>
$str
</body>
</html>
_EOD;

	// 携帯からのアクセス用に Shift-JIS で出力
	header("Content-Type: text/html;charset=shift_jis");
	echo mb_convert_encoding(str_replace("\n","",$out),"SJIS","EUC-JP");
	exit;
}

?>