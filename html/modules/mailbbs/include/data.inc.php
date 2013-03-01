<?php
function mailbbs_new($maxtopic, $offset=0)
{
	// 設定読み込み
	include (XOOPS_ROOT_PATH.'/modules/mailbbs/'."config.php");
	$log = array();
	$logfile = preg_replace("#^\./data/#","",$log);
	
	$ret = array();
	$file = @file(XOOPS_ROOT_PATH.'/modules/mailbbs/data/'.$logfile);
	$recent = array_map('rtrim',$file);
	
	// XOOPSサニタイザ
	$myts =& MyTextSanitizer::getInstance();
	
	$page = 0;
	foreach ($recent as $line)
	{
		if ($page > $maxtopic) break;
		
		$elems = array_pad(explode("<>",trim($line)),8,"");
		
		if ($elems[7]) continue; //未承認
		
		$id = $elems[0];
		$time = $elems[1];
		$title = $elems[2];
		
		// コメント件数
		$c_count = count(explode("</>",$elems[6])) - 1;
		
		// 投稿者名抽出(対応するには WhatsNew 本体に改造が必要)
		$match = array();
		$uname = (preg_match("/by\s+(.+)$/",$elems[4],$match))? $match[1] : "";
		
		// 最新コメントがあれば上書き
		if(preg_match("/([^>]*)\t(?:[^\t]*)([\d]{10})<\/>$/",$elems[6],$match))
		{
			$uname = $match[1];
			$time = $match[2];
		}
		
		// 本文データ整形
		$body = $elems[4];
		$body = $myts->displayTarea(str_replace(array("&lt;","&gt;","<br />"),array("<",">","\n"),$body));
		
		// コメント
		$comments = $elems[6];
		if ($comments)
		{
			$c_array = array_reverse(explode("</>",$comments));
			$comments = "";
			$_time = 0;
			foreach($c_array as $comment)
			{
				if ($comment)
				{
					if (!$_time) $_time = substr($comment,-10);
					$comment = substr($comment,0,strlen($comment)-10);
					list($name,$comment) = explode("\t",$comment);
					$comments .= "<strong>".preg_replace("/(^<p>|(<br \/>\n)?<\/p>$)/","",$myts->displayTarea($name))."</strong>: ".preg_replace("/(^<p>|(<br \/>\n)?<\/p>$)/","",$myts->displayTarea($comment))." - <small>".date('y/m/d G:i',$time)."</small><br />\n";
				}
			}
			$comments .= "<hr />\n";
			if ($_time) $time = $_time;
		}

		//$elems[6] = str_replace(array("&lt;","&gt;"),array("<",">"),$elems[6]);
		//$elems[6] = preg_replace("/[\d]{10}<\/>/","<br>\n",$elems[6]);
		
		// イメージのサイズ
		if ($elems[5])
		{
			$image = 
			(file_exists(XOOPS_ROOT_PATH."/modules/mailbbs/imgs/s/".$elems[5]))?
				XOOPS_ROOT_PATH."/modules/mailbbs/imgs/s/".$elems[5]
				:
				(
					(file_exists(XOOPS_ROOT_PATH."/modules/mailbbs/imgs/".$elems[5]))?
						XOOPS_ROOT_PATH."/modules/mailbbs/imgs/".$elems[5]
						:
						""
				)
			;
			if ($image) { @list($width, $height, $type, $attr) = @getimagesize($image);}
			if ($width < 2) $image = "";
		}
		
		$ret[$page]['link'] =XOOPS_URL."/modules/mailbbs/index.php?mode=flat&amp;id=".$id;
		$ret[$page]['title'] = $title;
		$ret[$page]['replies'] = $c_count;
		$ret[$page]['uid'] = 0;
		$ret[$page]['uname'] = $uname;
		$ret[$page]['time'] = $time;
		//$ret[$page]['description'] = str_replace("<br />","<br>\n",$elems[4])."<br>\n<br>\n".$elems[6];
		$ret[$page]['description'] = $comments.$body;
		if ($image)
		{
			$ret[$page]['image'] = str_replace(XOOPS_ROOT_PATH,XOOPS_URL,$image);
			if ($width) {$ret[$page]['width'] = $width;}
			if ($height) {$ret[$page]['height'] = $height;}
		}
		$page++;
	}
	return $ret;
}
?>