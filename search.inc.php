<?php
function mailbbs_search($keys, $type='AND', $limit=0, $offset=0, $userid=0)
{
	if ($userid) return array();
	
	include(XOOPS_ROOT_PATH."/modules/mailbbs/config.php");
	$log = preg_replace("#^\./#","",$log);
	
	$file = XOOPS_ROOT_PATH."/modules/mailbbs/".$log;

	$logs = file($file);

	$b_type = ($type == 'AND'); // AND:TRUE OR:FALSE

	$ret = array();
	$i = 0;
	foreach ($logs as $log)
	{
		$data = array_pad(explode("<>",$log),8,"");
		
		if ($data[7]) continue; //未承認
		
		$data[2] = str_replace(array("&lt;","&gt;"),array("<",">"),$data[2]);
		$data[4] = str_replace(array("&lt;","&gt;"),array("<",">"),$data[4]);
		$data[6] = str_replace(array("&lt;","&gt;"),array("<",">"),$data[6]);
		$data[6] = preg_replace("/[\d]{10}<\/>/","",$data[6]);
		$source = array($data[2],$data[4],$data[5],$data[6]);
		foreach ($keys as $key)
		{
			$key = preg_quote($key,"/");
			$tmp = preg_grep("/$key/",$source);
			$b_match = (count($tmp) > 0);
			if ($b_match xor $b_type)
			{
				break;
			}
		}
		if ($b_match)
		{
			$ret[$i]['link'] = "?mode=flat&id=".$data[0];
			$ret[$i]['title'] = $data[2];
			$ret[$i]['image'] = "keitai.gif";
			$ret[$i]['time'] = $data[1];
			$ret[$i]['uid'] = 0;
			if (function_exists("xoops_make_context"))
			{
				$queryarray = array();
				$ret[$i]['context'] = xoops_make_context(htmlspecialchars(strip_tags(str_replace("<br />","\n",$data[4])." ".$data[6])),$queryarray);
			}
			$i++;
		}
	}
	if ($limit==0)
	{
		return array_slice($ret,$offset);
	}
	else
	{
		return array_slice($ret,$offset,$limit);
	}
}

// 検索語を展開する
function mailbbs_get_search_words($words,$special=FALSE)
{
	$retval = array();
	// Perlメモ - 正しくパターンマッチさせる
	// http://www.din.or.jp/~ohzaki/perl.htm#JP_Match
	$eucpre = $eucpost = '';
	//if (SOURCE_ENCODING == 'EUC-JP')
	//{
		$eucpre = '(?<!\x8F)';
		// # JIS X 0208 が 0文字以上続いて # ASCII, SS2, SS3 または終端
		$eucpost = '(?=(?:[\xA1-\xFE][\xA1-\xFE])*(?:[\x00-\x7F\x8E\x8F]|\z))';
	//}
	// $special : htmlspecialchars()を通すか
	$quote_func = create_function('$str',$special ?
		'return preg_quote($str,"/");' :
		'return preg_quote(htmlspecialchars($str),"/");'
	);
	// LANG=='ja'で、mb_convert_kanaが使える場合はmb_convert_kanaを使用
	$convert_kana = create_function('$str,$option',
		(function_exists('mb_convert_kana')) ?
			'return mb_convert_kana($str,$option);' : 'return $str;'
	);
	
	foreach ($words as $word)
	{
		// 英数字は半角,カタカナは全角,ひらがなはカタカナに
		$word_zk = $convert_kana($word,'aKCV');
		$chars = array();
		for ($pos = 0; $pos < mb_strlen($word_zk);$pos++)
		{
			$char = mb_substr($word_zk,$pos,1);
			$arr = array($quote_func($char));
			if (strlen($char) == 1) // 英数字
			{
				$_char = strtoupper($char); // 大文字
				$arr[] = $quote_func($_char);
				$arr[] = $quote_func($convert_kana($_char,"A")); // 全角
				$_char = strtolower($char); // 小文字
				$arr[] = $quote_func($_char);
				$arr[] = $quote_func($convert_kana($_char,"A")); // 全角
			}
			else // マルチバイト文字
			{
				$arr[] = $quote_func($convert_kana($char,"c")); // ひらがな
				$arr[] = $quote_func($convert_kana($char,"k")); // 半角カタカナ
			}
			$chars[] = '(?:'.join('|',array_unique($arr)).')';
		}
		$retval[$word] = $eucpre.join('',$chars).$eucpost;
	}
	return $retval;
}

?>