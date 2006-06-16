<?php
// 削除処理
function mailbbs_log_del($lines,$ret_mode=false){
	global $delpass,$tmpdir,$thumb_dir,$log;
	global $mailbbs_head_dir,$mailbbs_head_prefix;
	global $mailbbs_allowlog,$X_admin;
	global $xoopsHypTicket;
	
	if ( !$ret_mode  && !$xoopsHypTicket->check() )
	{
		if ($ret_mode)
		{
			$ret = (!empty($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF'];
			redirect_header($ret,1,$xoopsHypTicket->getErrors());
		}
	}
	
	$find = false;
	for ($i=0; $i<count($lines); $i++)
	{
		list($id, $ptime, $subject, $from, $body, $att,$comments) = explode("<>", $lines[$i]);
		$_POST['del'][$id] = (isset($_POST['del'][$id]))? $_POST['del'][$id] : "";
		if ($_POST['del'][$id] == "on")
		{
			if($X_admin || $_POST['pass'] == $delpass || $_POST['pass'] == $from)
			{
				$lines[$i] = "";
				if (file_exists($tmpdir.$att))
					@unlink($tmpdir.$att);
				if (file_exists($thumb_dir.preg_replace("/\.[^\.]+$/","",$att).".jpg"))
					@unlink($thumb_dir.preg_replace("/\.[^\.]+$/","",$att).".jpg");
				if (file_exists($mailbbs_head_dir.$mailbbs_head_prefix.$id.".cgi"))
					@unlink($mailbbs_head_dir.$mailbbs_head_prefix.$id.".cgi");
				$find = true;
				
				//管理人削除時 承認済みメールアドレスから削除
				if ($X_admin)
				{
					$allow_mails = file($mailbbs_allowlog);
					$allow_mails = array_map('rtrim',$allow_mails);
					$_mails = array();
					foreach($allow_mails as $_mail)
					{
						if ($_mail == $from) continue;
						$_mails[] = $_mail;
					}
					$fp = fopen($mailbbs_allowlog, "wb");
					flock($fp, LOCK_EX);
					fputs($fp, join("\n",$_mails));
					flock($fp, LOCK_UN);
					fclose($fp);
				}

			}
		}
	}
	if ($find)
	{
		$fp = fopen($log, "wb");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $lines));
		fclose($fp);
		if ($ret_mode) return TRUE;
		redirect_header($_SERVER['PHP_SELF'],1,"投稿記事の削除処理が完了しました。");
		exit();
	} else {
		$ret = (!empty($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF'];
		if ($ret_mode) return FALSE;
		redirect_header($ret,2,"メールアドレスが一致しません！！<br />チェックを入れ、投稿時のメールアドレスを入力して下さい。");
		exit();
	}
}

// コメント削除処理
function mailbbs_log_comment_del($lines){
	global $delpass,$tmpdir,$log;
	global $mailbbs_head_dir,$mailbbs_head_prefix;
	global $X_admin;
	global $xoopsHypTicket;
	
	if ( ! $xoopsHypTicket->check() )
	{
		$ret = (!empty($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF'];
		redirect_header($ret,1,$xoopsHypTicket->getErrors());
	}
	
	$find = false;
	$ret_place = array();
	
	for ($i=0; $i<count($lines); $i++) {
		list($id, $ptime, $subject, $from, $body, $att,$comments) = explode("<>", trim($lines[$i]));
		$_POST['del_com'][$id] = (isset($_POST['del_com'][$id]))? $_POST['del_com'][$id] : array();
		if ($_POST['del_com'][$id]) {
			if($X_admin || $_POST['pass'] == $delpass || $_POST['pass'] == $from)
			{
				$c_array = explode("</>",$comments);
				foreach($_POST['del_com'][$id] as $key => $val)
				{
					if ($val == "on")
					{
						$c_array[$key] = "";
						$find = true;
					}
				}
				$comments = join("</>",$c_array);
				$comments = preg_replace("/(<\/>)+/","</>",$comments);
				if ($comments == "</>" || !$comments)
				{
					// コメントがなくなった場合
					$comments = "";
				}
				$lines[$i] = "";
				$ret_place[] = "$id<>$ptime<>$subject<>$from<>$body<>$att<>$comments\n";
			}
		}
	}
	
	// コメントがなくなった場合、元記事のタイムスタンプで元の場所に戻す
	if ($ret_place)
	{
		foreach($ret_place as $ins)
		{
			$ins_data = explode("<>", trim($ins));
			$i_time = $ins_data[1];
			// コメントがあれば最新コメントのタイムスタンプを取得
			$times = array();
			if(preg_match_all("/([\d]{10})<\/>/",$ins_data[6],$times,PREG_PATTERN_ORDER))
			{
				$times = $times[1];
				$times[] = $i_time;
				$i_time = max($times);
			}
			
			$_lines = array();
			$done =false;
			foreach($lines as $line)
			{
				$data = explode("<>", trim($line));
				$time = $data[1]; // 比較記事タイムスタンプ
				
				// コメントがあれば最新コメントのタイムスタンプを取得
				if(preg_match_all("/([\d]{10})<\/>/",$data[6],$times,PREG_PATTERN_ORDER))
				{
					$times = $times[1];
					$times[] = $time;
					$time = max($times);
				}
				if (!$done && ($time && $i_time > $time))
				{
					$done = true;
					$_lines[] = $ins;
					$_lines[] = $line;
				}
				else
					$_lines[] = $line;
			}
			if (!done) $_lines[] = $ins;
			$lines = $_lines;
		}
	}
	
	if ($find) {
		$fp = fopen($log, "wb");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $lines));
		fclose($fp);
		redirect_header($_SERVER['PHP_SELF'],1,"コメントの削除処理が完了しました。");
		exit();
	} else {
		$ret = (!empty($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF'];
		redirect_header($ret,2,"メールアドレスが一致しません！！<br />
チェックを入れ、投稿時のメールアドレスを入力して下さい。");
		exit();
	}
}

// コメント挿入処理
function mailbbs_log_comment($lines,$nox=0){
	global $delpass,$tmpdir,$log;
	global $mailbbs_head_dir,$mailbbs_head_prefix,$mailbbs_nosign;
	global $xoopsConfig,$notification;
	global $xoopsHypTicket, $mailbbs_commentspam_ch_setting;
	
	if ($nox)
	{
		// docomo の一部機種で相対URIが受け付けられないらしい・・・。
		$rid = array_keys($_POST['comment']);
		$jump = ($_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT'] == 80 ? '' : ':' . $_SERVER['SERVER_PORT']).$_SERVER['PHP_SELF']."?id=".$rid[0];
	}
	if ( ! $xoopsHypTicket->check() )
	{
		if (!$nox)
		{
			$ret = (!empty($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF'];
			redirect_header($ret,1,$xoopsHypTicket->getErrors());
		}
		else
		{
			put_html_nox("ERROR: ".$xoopsHypTicket->getErrors().'<hr>[ <a href="'.$jump.'">Reload</a> ]');
		}
	}
	
	$find = false;
	$rid = 0;
	$_lines = array();
	for ($i=0; $i<count($lines); $i++) {
		list($id, $ptime, $subject, $from, $body, $att, $comments) = explode("<>", trim($lines[$i]));
		$_comment = (!empty($_POST['comment'][$id]))? $_POST['comment'][$id] : "";
		
		if (!empty($mailbbs_commentspam_ch_setting))
		{
			//t_miyabi add-->
			$match = array();
			if (preg_match_all("#https?://#i",$_comment ,$match,PREG_PATTERN_ORDER))
			{
				if (count($match[0]) > $mailbbs_commentspam_ch_setting) exit();
			}
			// <--t_miyabi add
		}
		
		if ($_comment)
		{
			$_name = (!empty($_POST['name'][$id]))? $_POST['name'][$id] : "";
			if ($nox)
			{
				$_name = mb_convert_encoding($_name, "EUC-JP", "SJIS");
				$_comment = mb_convert_encoding($_comment, "EUC-JP", "SJIS");
				$_comment = preg_replace("/(\x0D\x0A|\x0D|\x0A)+/"," ",$_comment);
			}
			$m_comment = $_comment;
			$m_name = $_name = ($_name)? $_name : $mailbbs_nosign;
			setcookie("mailbbs_un", $_name, time()+86400*365);//1年間
			$_name = str_replace(array("<",">","\t"),array("&lt;","&gt;",""),$_name);
			$_comment = str_replace(array("<",">"),array("&lt;","&gt;"),$_comment);
				$comments .= $_name."\t".$_comment.time()."</>";
				//$lines[$i] = "";
				array_unshift($_lines, "$id<>$ptime<>$subject<>$from<>$body<>$att<>$comments\n");
				$find = true;
				$rid = $id;
			
			// メール通知
			if ($notification)
			{
				$xoopsMailer =& getMailer();
				$m_subject = "写メールBBSコメント通知:ID[$id]";
				$m_url = XOOPS_URL."/modules/mailbbs/?id=".$id;
				$m_body =<<<_EOD
記事:$m_url

Name: $m_name

コメント:
$m_comment
_EOD;
				$xoopsMailer->useMail();
				$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
				$xoopsMailer->setFromName($xoopsConfig['sitename']);
				$xoopsMailer->setSubject($m_subject);
				$xoopsMailer->setBody($m_body);
				$xoopsMailer->setToEmails($xoopsConfig['adminmail']);
				$xoopsMailer->send();
				$xoopsMailer->reset();
			}
		}
		else
		{
			$_lines[] = $lines[$i];
		}
	}
	$lines = $_lines;
	if ($find) {
		$fp = fopen($log, "wb");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $lines));
		fclose($fp);
		//$ret = (!empty($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF'];
	if (!$nox)
			redirect_header($_SERVER['PHP_SELF'],1,"コメントを受け付けました。");
		else
		{
			header("Location: $jump");
		}
		exit();
	}
	else
		return $lines;
}

// 記事承認処理
function mailbbs_log_allow($lines,$ret_mode=false)
{
	global $log, $mailbbs_allowlog;
	global $xoopsHypTicket;

	if ( !$ret_mode  && !$xoopsHypTicket->check() )
	{
		if ($ret_mode)
		{
			$ret = (!empty($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF'];
			redirect_header($ret,1,$xoopsHypTicket->getErrors());
		}
	}
	
	$find = false;
	for ($i=0; $i<count($lines); $i++)
	{
		list($id, $ptime, $subject, $from, $body, $att,$comments,$allow) = array_pad(explode("<>", $lines[$i]),8,"");
		$_POST['allow'][$id] = (isset($_POST['allow'][$id]))? $_POST['allow'][$id] : "";
		if ($_POST['allow'][$id] == "on")
		{
			$lines[$i] = "$id<>$ptime<>$subject<>$from<>$body<>$att<>$comments<>0\n";
			$find = true;
			
			$allow_mails = file($mailbbs_allowlog);
			$allow_mails = array_map('rtrim',$allow_mails);
			$allow_mails[] = $from;
			$allow_mails = array_unique($allow_mails);
			
			$fp = fopen($mailbbs_allowlog, "wb");
			flock($fp, LOCK_EX);
			fputs($fp, join("\n",$allow_mails));
			flock($fp, LOCK_UN);
			fclose($fp);
		}
	}
	if ($find)
	{
		$fp = fopen($log, "wb");
		flock($fp, LOCK_EX);
		fputs($fp, implode('', $lines));
		fclose($fp);
		if (function_exists('xoops_update_rpc_ping')) xoops_update_rpc_ping();
		if ($ret_mode) return TRUE;
		redirect_header($_SERVER['PHP_SELF'],1,"投稿記事の承認処理が完了しました。");
		exit();
	} else {
		$ret = (!empty($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF'];
		if ($ret_mode) return FALSE;
		redirect_header($ret,2,"指定された記事が見つかりませんでした。");
		exit();
	}
}

function mailbbs_check_array($data)
{
	if (!is_array($data)) return false;
	$ret = false;
	foreach($data as $val)
	{
		if (!empty($val))
		{
			$ret = true;
			break;
		}
	}
	return $ret;
}

function mailbbs_log_get()
{
	global $log, $X_admin, $X_uname;
	
	$ret = array();
	
	if ($X_admin)
	{
		$ret = file($log);
	}
	else
	{
		foreach(file($log) as $line)
		{
			$data = array_pad(explode("<>",trim($line)),8,"");
			if (!$data[7])
			{
				$ret[]= $line;
			}
		}
	}
	
	return $ret;
	
}

function put_html_nox($str)
{
	$output = '<html><body>'.$str.'</body></html>';
	header("Content-type: text/html; charset=shift_jis");
	echo convert(str_replace(array("<br />","\r","\n"),array("<br>",""),$output));
	exit;
}

function mailbbs_rotate($att,$rc)
{
	global $tmpdir,$thumb_dir;
	
	$ret = HypCommonFunc::rotateImage($thumb_dir.preg_replace("/\.[^\.]+$/",".jpg",$att), $rc, 75);
	
	$size = filesize($tmpdir.$att) / 1024;
	$quality = ($size < 6000)? 75 : 90;
	$ret = HypCommonFunc::rotateImage($tmpdir.$att, $rc, $quality);
	
	return $ret;
}
?>