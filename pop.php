<?php
/****
	写メールBBS by ToR 2002/09/25
										 http://php.s3.to
	
	メール投稿型の掲示板です。添付画像に対応してます。
	専用のメールアドレスを用意した方がいいです。
	尚、受信したメールは削除されます。

	pop.php ------新着メールのチェック、メールがあればログに記録します。
	mailbbs.php --PC表示用
	j.php --------J-PHONE表示用
	riyou.html ---投稿方法解説ページ（各自編集してください)
*/
//10/29 v1.6 サイズオーバー時も空ログ記録してた
//12/17 v1.7 PlayOnline Mailerの添付に対応（ファイル名取得
//03/01/06 v1.8 ez.php更新。広告削除機能追加。
//03/01/14 v1.81 2件同時受信の時前のファイル名が残る為$attach = "";追加
//03/01/18 v1.9 添付メールのみ記録する設定追加
//03/01/25 v2.0 バウンダリに正規表現文字があっても化けないようにした
//03/02/05 v2.1 サーバ接続回りを変更、削除できるかな
//03/02/13 v2.2 日付を取り込み時刻では無く、ヘッダにある日付に変更
//03/02/24 v2.3 書込みをバイナリーにした。ﾊﾞｲﾅﾘ
//03/05/23 v2.5 接続処理変更、エラー表示
//03/06/04 v2.51 拒否拡張子の追加（pif,scr)
//03/07/17 v2.6 非表示に変更
//03/07/24 v2.61 更新後はheaderで飛ばす、本文文字制限

/*-----------------*/
include_once('../../mainfile.php');
include_once('config.php');
include_once("include/hyp_common_func.php");
//include_once('thumb.php');
/*-----------------*/

// 遅延指定
$sleep = (isset($_GET['sleep']))? (int)$_GET['sleep'] : 0;
$sleep = min($sleep,10);
sleep($sleep);

// allow ファイルチェック なければ投稿済みデータから作成
if ($mailbbs_allowlog)
{
	$allow_mails = array();
	if (!file_exists($mailbbs_allowlog))
	{
		$allow_mails = array();
		foreach(file($log) as $line)
		{
			$lines = explode("<>",rtrim($line));
			$allow_mails[] = $lines[3];
		}
		$allow_mails = array_unique($allow_mails);
		
		$fp = fopen($mailbbs_allowlog, "wb");
		flock($fp, LOCK_EX);
		fputs($fp, join("\n",$allow_mails));
		flock($fp, LOCK_UN);
		fclose($fp);
	}
	else
	{
		$allow_mails = array_map('rtrim',file($mailbbs_allowlog));
	}
}

if (isset($_GET['mode'])) {
	if ($_GET['mode']=="flat") $backmode = "?mode=flat";
	elseif ($_GET['mode']=="list") $backmode = "?mode=list";
} else {
	$backmode = "";
}

$img_mode = false;
if (isset($_GET['img'])) {
	if ($_GET['img']) $img_mode = true;
}

//$jump = "http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
//$jump = preg_replace("/[^\/]+$/","",$jump).$backmode;
$jump = XOOPS_URL."/modules/mailbbs/".$backmode;

if (!extension_loaded('mbstring'))
{
	// mb_関数が使えない場合http://www.spencernetwork.org/にて漢字コード変換(簡易版)を入手して下さい
	if (file_exists("jcode-LE.php"))
	{
		include_once("jcode-LE.php");
	}
	else
	{
		exit('This server is not support "mbstring" please upload "jcode-LE.php"(http://www.spencernetwork.org/).');
	}
}

// 接続開始
$sock = fsockopen($host, 110, $err, $errno, 10) or error_output("サーバーに接続できません。");
$buf = fgets($sock, 512);
if(substr($buf, 0, 3) != '+OK')
{
	error_output($buf);
}
$buf = _sendcmd("USER $user");
$buf = _sendcmd("PASS $pass");
$data = _sendcmd("STAT");//STAT -件数とサイズ取得 +OK 8 1234
sscanf($data, '+OK %d %d', $num, $size);

if ($num == "0") {
	$buf = _sendcmd("QUIT"); //バイバイ
	fclose($sock);
	// logファイルのファイルスタンプを更新
	@touch($log);
	
	if (!$img_mode){
		header("Location: $jump");
	} else {
		// imgタグ呼び出し用
		header("Content-Type: image/gif");
		readfile('spacer.gif');
	}
	exit;
}
// 件数分
for($i=1;$i<=$num;$i++) {
	$line = _sendcmd("RETR $i");//RETR n -n番目のメッセージ取得（ヘッダ含
	$dat[$i] = "";
	while (!ereg("^\.\r\n",$line)) {//EOFの.まで読む
		$line = fgets($sock,512);
		$dat[$i].= $line;
	}
	$data = _sendcmd("DELE $i");//DELE n n番目のメッセージ削除
}
$buf = _sendcmd("QUIT"); //バイバイ
fclose($sock);

$lines = array();
$lines = @file($log);
$lines = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$lines);

for($j=1;$j<=$num;$j++) {
	$write = true;
	$subject = $from = $text = $atta = $part = $attach = "";
	list($head, $body) = mime_split($dat[$j]);
	// To:ヘッダ確認
	if (preg_match("/(?:^|\n|\r)To:[ \t]*([^\r\n]+)/i", $head, $treg)){
		$toreg = "/".quotemeta($mail)."/";
		if (!preg_match($toreg,$treg[1])) $write = false; //投稿アドレス以外
	} else {
		// To: ヘッダがない
		$write = false;
	}
	// メーラーのチェック
	if ($write && (eregi("(X-Mailer|X-Mail-Agent):[ \t]*([^\r\n]+)", $head, $mreg))) {
		if ($deny_mailer){
			if (preg_match($deny_mailer,$mreg[2])) $write = false;
		}
	}
	// キャラクターセットのチェック
	if ($write && (eregi("charset[\s]*=[\s]*([^\r\n]+)", $head, $mreg))) {
		if ($deny_lang){
			if (preg_match($deny_lang,$mreg[1])) $write = false;
		}
	}
	// 日付の抽出
	eregi("Date:[ \t]*([^\r\n]+)", $head, $datereg);
	$now = strtotime($datereg[1]);
	if ($now == -1) $now = time();
	// サブジェクトの抽出
	if (preg_match("/\nSubject:[ \t]*(.+?)(\n[\w-_]+:|$)/is", $head, $subreg)) {
		// 改行文字削除
		$subject = str_replace(array("\r","\n"),"",$subreg[1]);
		// エンコード文字間の空白を削除
		$subject = preg_replace("/\?=[\s]+?=\?/","?==?",$subject);
		
		while (eregi("(.*)=\?iso-[^\?]+\?B\?([^\?]+)\?=(.*)",$subject,$regs)) {//MIME B
			$subject = $regs[1].base64_decode($regs[2]).$regs[3];
		}
		while (eregi("(.*)=\?iso-[^\?]+\?Q\?([^\?]+)\?=(.*)",$subject,$regs)) {//MIME Q
			$subject = $regs[1].quoted_printable_decode($regs[2]).$regs[3];
		}
		$subject = trim(convert($subject));
		
		//回転指定コマンド検出
		$rotate = 0;
		if (preg_match("/(.+)(?:(r|l)@)$/i",$subject,$match))
		{
			$subject = $match[1];
			$rotate = (strtolower($match[2]) == "r")? 1 : 3;
		}
		
		$subject = htmlspecialchars($subject);
		
		// 未承諾広告カット
		if ($write && $deny_title){
			if (preg_match($deny_title,$subject)) $write = false;
		}
	}
	// 送信者アドレスの抽出
	if (eregi("From:[ \t]*([^\r\n]+)", $head, $freg)) {
		$from = addr_search($freg[1]);
	} elseif (eregi("Reply-To:[ \t]*([^\r\n]+)", $head, $freg)) {
		$from = addr_search($freg[1]);
	} elseif (eregi("Return-Path:[ \t]*([^\r\n]+)", $head, $freg)) {
		$from = addr_search($freg[1]);
	}
	// 拒否アドレス
	if ($write){
		for ($f=0; $f<count($deny); $f++)
			if (eregi($deny[$f], $from)) $write = false;
	}

	// マルチパートならばバウンダリに分割
	if (eregi("\nContent-type:.*multipart/",$head)) {
		eregi('boundary="([^"]+)"', $head, $boureg);
		$body = str_replace($boureg[1], urlencode($boureg[1]), $body);
		$part = split("\r\n--".urlencode($boureg[1])."-?-?",$body);
		if (eregi('boundary="([^"]+)"', $body, $boureg2)) {//multipart/altanative
			$body = str_replace($boureg2[1], urlencode($boureg2[1]), $body);
			$body = eregi_replace("\r\n--".urlencode($boureg[1])."-?-?\r\n","",$body);
			$part = split("\r\n--".urlencode($boureg2[1])."-?-?",$body);
		}
	} else {
		$part[0] = $dat[$j];// 普通のテキストメール
	}
	
	foreach ($part as $multi) {
		list($m_head, $m_body) = mime_split($multi);
		$m_body = ereg_replace("\r\n\.\r\n$", "", $m_body);
		// キャラクターセットのチェック
		if ($write && (eregi("charset[\s]*=[\s]*([^\r\n]+)", $m_head, $mreg))) {
			if ($deny_lang){
				if (preg_match($deny_lang,$mreg[1])) $write = false;
			}
		}
		if (!eregi("Content-type: *([^;\n]+)", $m_head, $type)) continue;
		list($main, $sub) = explode("/", $type[1]);
		// 本文をデコード
		if (strtolower($main) == "text") {
			if (eregi("Content-Transfer-Encoding:.*base64", $m_head)) 
				$m_body = base64_decode($m_body);
			if (eregi("Content-Transfer-Encoding:.*quoted-printable", $m_head)) 
				$m_body = quoted_printable_decode($m_body);
			$text = trim(convert($m_body));
			if ($sub == "html") $text = strip_tags($text);
			// 拒否本文のチェック
			if ($write && isset($deny_body))
			{
				if (preg_match($deny_body,$text)) $write = false;
			}			
			$text = str_replace(">","&gt;",$text);
			$text = str_replace("<","&lt;",$text);
			$text = str_replace("\r\n", "\r",$text);
			$text = str_replace("\r", "\n",$text);
			$text = preg_replace("/\n{2,}/", "\n\n", $text);
			$text = str_replace("\n", "<br />", $text);
			if ($write) {
				// 電話番号削除
				$text = eregi_replace("([[:digit:]]{11})|([[:digit:]\-]{13})", "", $text);
				// 下線削除
				$text = eregi_replace($del_ereg, "", $text);
				// mac削除
				$text = ereg_replace("Content-type: multipart/appledouble;[[:space:]]boundary=(.*)","",$text);
				// 広告等削除
				if (is_array($word)) {
					foreach ($word as $delstr)
						$text = str_replace($delstr, "", $text);
				}
				// 削除する文言
				if (is_array($del_reg))
				{
					foreach ($del_reg as $delstr)
					{
						if ($delstr)
						{
							$text = preg_replace($delstr, "", $text);
						}
					}
				}
				
				if (strlen($text) > $body_limit) $text = substr($text, 0, $body_limit)."...";
				// 署名抽出
				if (preg_match("/[\s　]*(by|BY|ｂｙ|ＢＹ)(,|\.|:|\s|　)?(.{1,20}?)(<br \/>|\n|$)/",$text,$reg_sign)){
					// 署名保存
					mailbbs_sign_set($from,htmlspecialchars($reg_sign[3]));
				} else { //署名なしの場合
					if ($text) $text .= mailbbs_sign_get($from);
				}
			}
		}
		if ($write) {
			// ファイル名を抽出
			if (eregi("name=\"?([^\"\n]+)\"?",$m_head, $filereg)) {
				$filename = trim($filereg[1]);
				// エンコード文字間の空白を削除
				$filename = preg_replace("/\?=[\s]+?=\?/","?==?",$filename);
				while (eregi("(.*)=\?iso-[^\?]+\?B\?([^\?]+)\?=(.*)",$filename,$regs)) {//MIME B
					$filename = $regs[1].base64_decode($regs[2]).$regs[3];
				}
				$filename = time()."-".convert($filename);
			}
			// 添付データをデコードして保存
			if (eregi("Content-Transfer-Encoding:.*base64", $m_head) && eregi($subtype, $sub)) {
				$tmp = base64_decode($m_body);
				if (!$filename) $filename = time().".$sub";
				if (strlen($tmp) < $maxbyte && !eregi($viri, $filename) && $write) {
					$fp = fopen($tmpdir.$filename, "wb");
					fputs($fp, $tmp);
					fclose($fp);
					$link = rawurlencode($filename);
					$attach = $filename;
					//サムネイル
					$size = getimagesize($tmpdir.$filename);
					if ($size[0] > $w || $size[1] > $h) {
						thumb_create($tmpdir.$filename,$w,$h,$thumb_dir);
					}
					//回転指定
					if ($rotate)
					{
						HypCommonFunc::rotateImage($tmpdir.$filename, $rotate);
						HypCommonFunc::rotateImage($thumb_dir.$filename, $rotate);
					}
				} else {
					$write = false;
				}
			}
		}
	}
	if ($imgonly && !$attach) $write = false;
	if (!$attach && !$text) $write = false;
	
	//list($old,,,,,) = explode("<>", $lines[0]);
	$old = array();
	foreach($lines as $line)
	{
		list($_old,,,,,,,) = array_pad(explode("<>", $line),8,"");
		$old[] = $_old;
	}
	$old = max($old);
	
	$id = $old + 1;
	if(trim($subject)=="") $subject = $nosubject;
	$allow = ( !$mailbbs_allowlog || in_array($from,$allow_mails) )? 0 : 1;
	$line = "$id<>$now<>$subject<>$from<>$text<>$attach<><>$allow\n";

	if ($write) {
		array_unshift($lines, $line);
		
		// ヘッダ情報を記録
		if ($mailbbs_head_save)
			mailbbs_head_save($id,$head);
	} else {
		// 拒否メールログ保存
		if ($mailbbs_denylog_save)
			mailbbs_deny_log($head,$subject,str_replace("<br />","\n",$text));
	}
}

//echo $debg;

// ログ最大行処理
if (count($lines) > $maxline) {
	for ($k=count($lines)-1; $k>=$maxline; $k--) {
		list($id,$tim,$sub,$fro,$tex,$at,) = explode("<>", $lines[$k]);
		if (file_exists($tmpdir.$at)) @unlink($tmpdir.$at);
		$lines[$k] = "";
	}
}
// ログに記録
if ($write) {
	$fp = fopen($log, "wb");
	flock($fp, LOCK_EX);
	fputs($fp, implode('', $lines));
	fclose($fp);
	
	// メール通知
	if ($notification)
	{
		include('../../mainfile.php');
		$xoopsMailer =& getMailer();
		global $xoopsConfig;
		
		$m_allow = $m_delete = $m_attach = "";
		
		if ($attach)
		{
			$m_attach =  "\n添付ファイル:\n".XOOPS_URL."/modules/mailbbs/".preg_replace("#^\./#","",$tmpdir).$filename;
			$s_name = preg_replace("/\.[^\.]+$/","",$filename).".jpg";
			if (file_exists($thumb_dir.$s_name))
			{
				$m_attach .= "\nサムネイル:\n".XOOPS_URL."/modules/mailbbs/".preg_replace("#^\./#","",$thumb_dir).$s_name;
			}
		}
		
		if ($mailbbs_allowlog)
		{
			if ($allow)
				$m_allow = XOOPS_URL."/modules/mailbbs/?a=a&p=".md5($id.$now.$host.$user.$pass);
			else
				$m_allow = "承認済み";
			
			$m_delete = XOOPS_URL."/modules/mailbbs/?a=d&p=".md5($id.$now.$host.$user.$pass);
		}
		
		$m_url = XOOPS_URL."/modules/mailbbs/?id=".$id;
		
		$m_text = str_replace(array("<br />","&lt;","&gt;"),array("\n","<",">"),$text);
		
		$m_subject = "写メールBBS投稿通知:ID[$id]";
		$m_body =<<<_EOD
承認: $m_allow

削除: $m_delete

記事: $m_url

投稿内容:
From: $from
----
$subject
----
$m_text
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
	
	// Send XML-RPC Update Ping by nao-pon
	if (function_exists('xoops_update_rpc_ping') && !$allow) xoops_update_rpc_ping();

} else {
	// logファイルのファイルスタンプを更新
	@touch($log);
}

if (!$img_mode){
	header("Location: $jump");
} else {
	// imgタグ呼び出し用
	header("Content-Type: image/gif");
	if ($write)
		readfile('mail.gif');
	else
		readfile('spacer.gif');
}
exit;


/* コマンドー送信！！*/
function _sendcmd($cmd) {
	global $sock,$jump;
	fputs($sock, $cmd."\r\n");
	$buf = fgets($sock, 512);
	if(substr($buf, 0, 3) == '+OK') {
		return $buf;
	} else {
		error_output($buf);
	}
	return false;
}
/* ヘッダと本文を分割する */
function mime_split($data) {
	$part = split("\r\n\r\n", $data, 2);
	$part[0] = ereg_replace("\r\n[\t ]+", " ", $part[0]);
	return $part;
}
/* メールアドレスを抽出する */
function addr_search($addr) {
	$fromreg = array();
	if (eregi("[-!#$%&\'*+\\./0-9A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+", $addr, $fromreg)) {
		return $fromreg[0];
	} else {
		return false;
	}
}
/* 文字コードコンバートauto→EUC-JP */
function convert($str,$code="EUC-JP") {
	if (function_exists('mb_convert_encoding')) {
		return mb_convert_encoding($str, $code, "auto");
	} elseif (function_exists('JcodeConvert')) {
		return JcodeConvert($str, 0, 1);
	}
	return true;
}

// 署名保存
function mailbbs_sign_set($from,$sign){
	global $mailbbs_signs;
	$_from = quotemeta($from);
	$_signs = @file($mailbbs_signs);
	$signs = trim($_signs[0]);
	$regptn = "/(^|\t)".$_from." ([^\t]+)(\t|$)/";
	if (preg_match($regptn,$signs)){
		$regptn = "/((^|\t)".$_from." )([^\t]+)(\t|$)/";
		$signs = preg_replace($regptn,"$1$sign$4",$signs);
	} else {
		$signs .= "\t".$from." ".$sign;
	}
	// 記録
	$fp = fopen($mailbbs_signs, "wb");
	flock($fp, LOCK_EX);
	fputs($fp, $signs);
	fclose($fp);
	return;
}

// 署名取得
function mailbbs_sign_get($from){
	global $mailbbs_signs,$mailbbs_nosign;
	$from = quotemeta($from);
	$_signs = @file($mailbbs_signs);
	$signs = trim($_signs[0]);
	$regptn = "/(^|\t)".$from." ([^\t]+)(\t|$)/";
	$sig_reg = array();
	if (preg_match($regptn,$signs,$sig_reg)){
		return "<br />by ".$sig_reg[2];
	}
	return "<br />by ".$mailbbs_nosign;
}
// 拒否メールログ保存
function mailbbs_deny_log($head,$subject,$body){
	global $mailbbs_denylog;
	$subject = unhtmlentities($subject);
	$body = unhtmlentities($body);
	// 記録
	$fp = fopen($mailbbs_denylog, "a+b");
	flock($fp, LOCK_EX);
	fputs($fp, "{$head}\n\nSubject: {$subject}\n\n{$body}\n\n\n");
	fclose($fp);
	return;
}
// ヘッダ情報を記録
function mailbbs_head_save($id,$head){
	global $mailbbs_head_dir,$mailbbs_head_prefix;
	// 記録
	$fp = fopen($mailbbs_head_dir.$mailbbs_head_prefix.$id.".cgi", "wb");
	flock($fp, LOCK_EX);
	fputs($fp, $head);
	fclose($fp);
	return;
}

// HTML エンティティを元に戻す
function unhtmlentities ($string)
{
	$trans_tbl = get_html_translation_table (HTML_ENTITIES);
	$trans_tbl = array_flip ($trans_tbl);
	return strtr ($string, $trans_tbl);
}

// エラー出力
function error_output ($str)
{
	global $jump,$img_mode;
	if ($img_mode)
	{
		header("Content-Type: image/gif");
		readfile('poperror.gif');
	}
	else
	{
		redirect_header($jump,3,$str);
	}
	exit;
}

// サムネイル
function thumb_create($src, $W, $H, $thumb_dir="./")
{
	$s_file = $thumb_dir.substr($src, strrpos($src,"/")+1);
	$s_file = preg_replace("/\.[^\.]+$/",".jpg",$s_file);
	HypCommonFunc::make_thumb($src, $s_file, $W, $H);
	return;
}
?>