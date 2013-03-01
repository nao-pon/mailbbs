<?php
function b_waiting_mailbbs()
{
	////////////////////////////////////
	//データディレクトリ名
	//$log_path = XOOPS_ROOT_PATH."/modules/mailbbs/data/";
	////////////////////////////////////
	//データファイルのファイル名
	//$log_file = "maillog.cgi";
	////////////////////////////////////
	//設定ここまで
	
	include(XOOPS_ROOT_PATH."/modules/mailbbs/config.php");
	$log = preg_replace("#^\./#","",$log);
	
	$file = XOOPS_ROOT_PATH."/modules/mailbbs/".$log;
	
	$ret = array();
	
	$block = array();
	
	$cnt = 0;
	if (file_exists($file))
	{
		foreach(@file($file) as $line)
		{
			$data = array_pad(explode("<>",rtrim($line)),8,"");
			if ($data[7]) $cnt++;
		}
	}
	
	$block['adminlink'] = XOOPS_URL."/modules/mailbbs/";
	$block['pendingnum'] = $cnt;
	$block['lang_linkname'] = _MB_WAITING_MAILBBS;
	
	$ret[] = $block;
	
	return $ret;
}
?>