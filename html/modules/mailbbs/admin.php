<?php
// admin.php for �̥᡼��BBS XOOPS by nao-pon
// at 2005/01/15

error_reporting(E_ALL);

if (!defined('MAILBBS_REG') || !empty($_SERVER['HTTP_REFERER'])) output("ERROR: ������������");

if (!isset($_GET['a']) || !isset($_GET['p'])) output("ERROR: ̵���ʥ�󥯤Ǥ�");

include("./func.php");
include_once("./include/hyp_tickets.php");

// �����ԥ⡼��
$X_admin = 1;

// �ǡ����ɤ߹���
$lines = mailbbs_log_get();

$found = 0;
$action = "";
foreach($lines as $line)
{
	$data = array_pad(explode("<>",rtrim($line)),8,"");
	
	// �����оݤ˥ޥå�
	if ($_GET['p'] === md5($data[0].$data[1].$host.$user.$pass))
	{
		// ��ǧ�⡼��
		if ($_GET['a'] == "a")
		{
			$_POST['allow'][$data[0]] = "on";
			if (!$data[7])
			{
				$found = 1;
				$action = "�Ͼ�ǧ�ѤߤǤ���";
				$num = $data[0];
				break;
			}
			if (mailbbs_log_allow($lines,TRUE))
			{
				$found = 1;
				$action = "��ǧ���ޤ�����";
				$num = $data[0];
				break;
			}
		}
		
		// ����⡼��
		else if ($_GET['a'] == "d")
		{
			$_POST['del'][$data[0]] = "on";
			if (mailbbs_log_del($lines,TRUE))
			{
				$found = 1;
				$action = "�������ޤ�����";
				$num = $data[0];
				break;
			}
		}
	}
}
if ($found)
{
	
	output("����ID.{$num}{$action}");
}
else
	output("���ꤵ�줿���������Ĥ���ޤ���");


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

	// ���Ӥ���Υ��������Ѥ� Shift-JIS �ǽ���
	header("Content-Type: text/html;charset=shift_jis");
	echo mb_convert_encoding(str_replace("\n","",$out),"SJIS","EUC-JP");
	exit;
}

?>