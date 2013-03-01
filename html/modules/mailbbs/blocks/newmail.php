<?PHP
function b_mailbbs_show($option,$show_new=false)
{
	$max = (empty($option[0]))? 1 : intval($option[0]);
	$attonly = (empty($option[1]))? 0 : 1;
	
	$mailbbs_path = XOOPS_ROOT_PATH."/modules/mailbbs/";
	$mailbbs_url = XOOPS_URL."/modules/mailbbs/";

	include($mailbbs_path.'/config.php');
	
	include_once($mailbbs_path."include/hyp_common/hyp_common_func.php");
	
	HypCommonFunc::str_to_entity($mail);

	$tmpurl = $mailbbs_url.$tmpdir;
	$tmpdir = $mailbbs_path.$tmpdir;

	$block = array();
	$block['title'] = '写メールBBS';
	if ($show_new) $block['title'] .= '(最新)';
	$block['content'] = "";

	//$lines = @file($mailbbs_path.$log);
	$lines = array();
	foreach(file($mailbbs_path.$log) as $line)
	{
		$data = array_pad(explode("<>",trim($line)),8,"");
		if (!$data[7] && (!$attonly || (eregi("\.(gif|jpe?g|png|bmp)$",$data[5]) && file_exists($tmpdir.$data[5]))))
		{
			$lines[]= $line;
		}
	}
	
	$shown = $subject = $com = $imgsrc = array();
	
	if (!$show_new) {shuffle($lines);}
	
	for($count=0; $count<$max; $count++)
	{
		if (empty($lines[$count])) break;
		
		list($id, $ptime, $_subject, $from, $body, $att, $comment, $flg) = array_pad(explode("<>", $lines[$count]),8,"");
		
		$subject[$id] = $_subject;
		
		$com[$id] = max(count(explode("</>",$comment)) - 1,0);
		$imgsrc[$id]= "";
		$date = date("Y-m-d G:i", $ptime);
		$size = (int)(@filesize($tmpdir.$att) / 1024);
		if (strstr($_SERVER["HTTP_USER_AGENT"], "MSIE")) $body = str_replace("<br />","\n",$body);
		$body = strip_tags($body);
		$l_body = str_replace("\"","",$body);
		//if ($_GET['mode'] == "admin" || $X_admin) $del = '<input type=checkbox name="del['.$id.']" value=on>';
		// 画像がある時
		if (preg_match('/\.(gif|jpe?g|png|bmp)$/i', $att) && file_exists($tmpdir.$att)){
			$href = $tmpdir.rawurlencode($att);
			$size = GetImageSize($tmpdir.$att);
			$size[0] = ($size[0])? $size[0]:$w;
			$size[1] = ($size[1])? $size[1]:$h;
			$isize = (int)(@filesize($tmpdir.$att) / 1024);
			$size_tag = ($size)? "\n$size[0]x$size[1]({$isize}KB)" : "\n({$isize}KB)";
			//if (!strstr($_SERVER["HTTP_USER_AGENT"], "MSIE")) $size_tag = str_replace("\n"," ",$size_tag);
			// リサイズ
			if ($size[0] > $w || $size[1] > $h) {
				$key_w = $w / $size[0];
				$key_h = $h / $size[1];
				($key_w < $key_h) ? $keys = $key_w : $keys = $key_h;
				$out_w = $size[0] * $keys;
				$out_h = $size[1] * $keys;
			} else {
				$out_w = $size[0];
				$out_h = $size[1];
			}
			$isize = getimagesize($tmpdir.$att);
			$winw = $isize[0] + 20;
			$winh = $isize[1] + 20;
			// サムネイルがある時、サムネイル表示。
			$filename = preg_replace("/\.[^\.]+$/","",$att);
			// サムネイルがある時、サムネイル表示。
			if (file_exists($mailbbs_path.$thumb_dir.$filename.".jpg")){
				$imgsrc[$id] = '<img src='.$mailbbs_url.$thumb_dir.rawurlencode($filename).".jpg".' border=0 title="'.$l_body.$size_tag.'" alt="'.$l_body.$size_tag.'" width='.$out_w.' height='.$out_h.'>';
			} else {
				$imgsrc[$id] = '<img src='.$tmpurl.rawurlencode($att).' border=0 title="'.$l_body.'" alt="'.$l_body.'" width='.$out_w.' height='.$out_h.'>';
			}
		}
	}
	
	$img_tags = array();
	foreach($imgsrc as $id=>$img_tag)
	{
		$img_tags[]= "<a href=\"{$mailbbs_url}?mode=flat&id={$id}\">$img_tag<br />{$subject[$id]}<br /><small>コメント: {$com[$id]}</small></a>\n";
	}
	$img_tags = join("<hr />",$img_tags);
	
	//メイン部分
	$url_mailbbs = XOOPS_URL . '/modules/mailbbs/';
	$outhtml = <<<EOM
<link rel="stylesheet" href="{$url_mailbbs}css/default.css" type="text/css" media="screen" charset="shift_jis">
<div class="mailbbs_block_content">
<a href="mailto:$mail">投稿</a>&nbsp;|&nbsp;<a href="{$mailbbs_url}?mode=flat">フラット</a>&nbsp;|&nbsp;<a href="{$mailbbs_url}?mode=list">一覧</a>&nbsp;|&nbsp;<a href="{$mailbbs_url}?help">?</a><br />
{$img_tags}
</div>
EOM;
	$block['content'] .= $outhtml;
	return $block;
}

function b_mailbbs_new($option) {
	return b_mailbbs_show($option,true);
}

function b_mailbbs_edit($options)
{
	$op2[0] = $op2[1] = "";
	$op2[$options[1]] = "selected=\"true\"";

	$form = "";
	$form .= "表示する件数: ";
	$form .= "<input type='text' size='4' name='options[]' value='".$options[0]."' />";
	$form .= "&nbsp;&nbsp;対象: ";
	$form .= "<select name='options[]'>";
	$form .= "<option value='0' {$op2[0]}>画像がある投稿のみ</option>";
	$form .= "<option value='1' {$op2[1]}>すべての投稿</option>";
	$form .= "</select>";
	return $form;
}
?>
