<?php
/****
	�̥᡼��BBS by ToR 2002/09/25
										 http://php.s3.to
	
	�᡼����Ʒ��ηǼ��ĤǤ���ź�ղ������б����Ƥޤ���
	���ѤΥ᡼�륢�ɥ쥹���Ѱդ������������Ǥ���
	�������������᡼��Ϻ������ޤ���

	pop.php ------����᡼��Υ����å����᡼�뤬����Х��˵�Ͽ���ޤ���
	mailbbs.php --PCɽ����
	j.php --------J-PHONEɽ����
	riyou.html ---�����ˡ����ڡ����ʳƼ��Խ����Ƥ�������)
*/
//10/29 v1.6 �����������С����������Ͽ���Ƥ�
//12/17 v1.7 PlayOnline Mailer��ź�դ��б��ʥե�����̾����
//03/01/06 v1.8 ez.php��������������ǽ�ɲá�
//03/01/14 v1.81 2��Ʊ�������λ����Υե�����̾���Ĥ��$attach = "";�ɲ�
//03/01/18 v1.9 ź�ե᡼��Τߵ�Ͽ���������ɲ�
//03/01/25 v2.0 �Х�����������ɽ��ʸ�������äƤⲽ���ʤ��褦�ˤ���
//03/02/05 v2.1 ��������³�����ѹ�������Ǥ��뤫��
//03/02/13 v2.2 ���դ�����߻���Ǥ�̵�����إå��ˤ������դ��ѹ�
//03/02/24 v2.3 ����ߤ�Х��ʥ꡼�ˤ������ʎގ��Ŏ�
//03/05/23 v2.5 ��³�����ѹ������顼ɽ��
//03/06/04 v2.51 ���ݳ�ĥ�Ҥ��ɲá�pif,scr)
//03/07/17 v2.6 ��ɽ�����ѹ�
//03/07/24 v2.61 �������header�����Ф�����ʸʸ������

/*-----------------*/
include_once('../../mainfile.php');
include_once('config.php');
include_once('func.php');
include_once("include/hyp_common/hyp_common_func.php");
/*-----------------*/

// �ٱ����
$sleep = (isset($_GET['sleep']))? (int)$_GET['sleep'] : 0;
$sleep = min($sleep,10);
sleep($sleep);

// allow �ե���������å� �ʤ������ƺѤߥǡ����������
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
	// mb_�ؿ����Ȥ��ʤ����http://www.spencernetwork.org/�ˤƴ����������Ѵ�(�ʰ���)�����ꤷ�Ʋ�����
	if (file_exists("jcode-LE.php"))
	{
		include_once("jcode-LE.php");
	}
	else
	{
		exit('This server is not support "mbstring" please upload "jcode-LE.php"(http://www.spencernetwork.org/).');
	}
}

// ��³����
$sock = fsockopen($host, 110, $err, $errno, 10) or error_output("�����С�����³�Ǥ��ޤ���");
$buf = fgets($sock, 512);
if(substr($buf, 0, 3) != '+OK')
{
	error_output($buf);
}
$buf = _sendcmd("USER $user");
$buf = _sendcmd("PASS $pass");
$data = _sendcmd("STAT");//STAT -����ȥ��������� +OK 8 1234
sscanf($data, '+OK %d %d', $num, $size);

if ($num == "0") {
	$buf = _sendcmd("QUIT"); //�Х��Х�
	fclose($sock);
	// log�ե�����Υե����륹����פ򹹿�
	@touch($log);
	
	if (!$img_mode){
		header("Location: $jump");
	} else {
		// img�����ƤӽФ���
		header("Content-Type: image/gif");
		readfile('spacer.gif');
	}
	exit;
}
// ���ʬ
for($i=1;$i<=$num;$i++) {
	$line = _sendcmd("RETR $i");//RETR n -n���ܤΥ�å����������ʥإå���
	$dat[$i] = "";
	while (!ereg("^\.\r\n",$line)) {//EOF��.�ޤ��ɤ�
		$line = fgets($sock,512);
		$dat[$i].= $line;
	}
	$data = _sendcmd("DELE $i");//DELE n n���ܤΥ�å��������
}
$buf = _sendcmd("QUIT"); //�Х��Х�
fclose($sock);

$lines = array();
$lines = @file($log);
$lines = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$lines);

for($j=1;$j<=$num;$j++) {
	$write = true;
	$error = $subject = $from = $text = $atta = $part = $attach = $charset = "";
	$bodys = array();
	$bodys['plain'] = array();
	$bodys['html'] = array();
	$rawdata = $dat[$j];
	list($head, $body) = mime_split($dat[$j]);

	// To:�إå���ǧ
	$treg = array();
	$to_ok = FALSE;
	if (preg_match("/(?:^|\n|\r)To:[ \t]*([^\r\n]+)/i", $head, $treg)){
		if (strpos($treg[1], $mail) !== FALSE) {
			$to_ok = TRUE;
		} else if (preg_match("/(?:^|\n|\r)X-Forwarded-To:[ \t]*([^\r\n]+)/i", $head, $treg)) {
			if (strpos($treg[1], $mail) !== FALSE) {
				$to_ok = TRUE;
			}
		}
	}
	if (! $to_ok) {
		$write = false;
		$error = 'deny_to';
	}

	// �᡼�顼�Υ����å�
	if ($write && (eregi("(X-Mailer|X-Mail-Agent):[ \t]*([^\r\n]+)", $head, $mreg))) {
		if ($deny_mailer){
			if (preg_match($deny_mailer,$mreg[2])) {
				$write = false;
				$error = 'deny_mailer';
			}
		}
	}
	// ����饯�������åȤΥ����å�
//	if ($write && (preg_match('/charset\s*=\s*"?([^";\s]+)/i', $head, $mreg))) {
//		$charset = $mreg[1];
//		if ($deny_lang){
//			if (preg_match($deny_lang,$charset)) $write = false;
//		}
//	}
	// ���դ����
	eregi("Date:[ \t]*([^\r\n]+)", $head, $datereg);
	$now = strtotime($datereg[1]);
	if ($now == -1) $now = time();

	// �����ԥ��ɥ쥹�����
	if (eregi("From:[ \t]*([^\r\n]+)", $head, $freg)) {
		$from = addr_search($freg[1]);
	} elseif (eregi("Reply-To:[ \t]*([^\r\n]+)", $head, $freg)) {
		$from = addr_search($freg[1]);
	} elseif (eregi("Return-Path:[ \t]*([^\r\n]+)", $head, $freg)) {
		$from = addr_search($freg[1]);
	}
	// ���ݥ��ɥ쥹
	if ($write){
		for ($f=0; $f<count($deny); $f++)
			if (eregi($deny[$f], $from)) {
				$write = false;
				$error = 'deny_address';
			}
	}

	// ���֥������Ȥ����
	if ($write && preg_match("/\nSubject:[ \t]*(.+?)(\n[\w-_]+:|$)/is", $head, $subreg)) {
		
		if (method_exists('HypCommonFunc', 'get_version') && HypCommonFunc::get_version() >= '20081215') {
			if (! class_exists('MobilePictogramConverter')) {
				HypCommonFunc::loadClass('MobilePictogramConverter');
			}
			$mpc =& MobilePictogramConverter::factory_common();
		}

		// ����ʸ�����
		$subject = str_replace(array("\r","\n"),"",$subreg[1]);
		// ���󥳡���ʸ���֤ζ������
		$subject = preg_replace("/\?=[\s]+?=\?/","?==?",$subject);
		
		while (eregi("(.*)=\?([^\?]+)\?B\?([^\?]+)\?=(.*)",$subject,$regs)) {//MIME B
			$_charset = $regs[2];
			$p_subject = base64_decode($regs[3]);
			if (isset($mpc)) {
				$p_subject = $mpc->mail2ModKtai($p_subject, $from, $_charset);
			}
			$subject = $regs[1].$p_subject.$regs[4];
		}
		while (eregi("(.*)=\?[^\?]+\?Q\?([^\?]+)\?=(.*)",$subject,$regs)) {//MIME Q
			$subject = $regs[1].quoted_printable_decode($regs[2]).$regs[3];
		}
		//��ž���ꥳ�ޥ�ɸ���
		$rotate = 0;
		if (preg_match("/(.*)(?:(r|l)@)$/i",$subject,$match))
		{
			$subject = rtrim($match[1]);
			$rotate = (strtolower($match[2]) == "r")? 1 : 3;
		}
		
		$subject = trim(convert($subject));
		
		$subject = htmlspecialchars($subject);
		
		// ̤�������𥫥å�
		if ($write && $deny_title){
			if (preg_match($deny_title,$subject)) {
				$write = false;
				$error = 'deny_title';
			}
		}
	}

	// �ޥ���ѡ��Ȥʤ�ХХ�������ʬ��
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
		$part[0] = $dat[$j];// ���̤Υƥ����ȥ᡼��
	}
	
	foreach ($part as $multi) {
		list($m_head, $m_body) = mime_split($multi);
		$m_body = ereg_replace("\r\n\.\r\n$", "", $m_body);
		// ����饯�������åȤΥ����å�
		if ($write && (preg_match('/charset\s*=\s*"?([^";\s]+)/i', $m_head, $mreg))) {
			$charset = $mreg[1];
			if ($deny_lang){
				if (preg_match($deny_lang,$charset)) {
					$write = false;
					$error = 'deny_charset';
				}
			}
		}
		if (!eregi("Content-type: *([^;\r\n]+)", $m_head, $type)) continue;
		list($main, $sub) = explode("/", $type[1]);
		$sub = strtolower(trim($sub));
		// ��ʸ��ǥ�����
		if (strtolower(trim($main)) === "text") {
			if (eregi("Content-Transfer-Encoding:.*base64", $m_head)) 
				$m_body = base64_decode($m_body);
			if (eregi("Content-Transfer-Encoding:.*quoted-printable", $m_head)) 
				$m_body = quoted_printable_decode($m_body);
			
			if (method_exists('HypCommonFunc', 'get_version') && HypCommonFunc::get_version() >= '20081215') {
				if (! isset($mpc)) {
					if (! class_exists('MobilePictogramConverter')) {
						HypCommonFunc::loadClass('MobilePictogramConverter');
					}
					$mpc =& MobilePictogramConverter::factory_common();
				}
				$m_body = $mpc->mail2ModKtai($m_body, $from, $charset);
			}
			
			$_text = trim(convert($m_body));
			if ($sub === 'html' || preg_match('#^<html>.*</html>$#is', $_text)) {
				$_text = preg_replace('#<head>.*</head>#is', '', $_text);
				$_text = strip_tags($_text);	
			}
			// ������ʸ�Υ����å�
			if ($write && isset($deny_body))
			{
				if (preg_match($deny_body,$_text)) {
					$write = false;
					$error = 'deny_body';
				}
			}
			$_text = htmlspecialchars($_text);
			$_text = str_replace("\r\n", "\r",$_text);
			$_text = str_replace("\r", "\n",$_text);
			$_text = preg_replace("/\n{2,}/", "\n\n", $_text);
			$_text = str_replace("\n", "<br />", $_text);
			
			if ($write) {
				// �����ֹ���
				$_text = eregi_replace("([[:digit:]]{11})|([[:digit:]\-]{13})", "", $_text);
				// �������
				$_text = eregi_replace($del_ereg, "", $_text);
				// mac���
				$_text = ereg_replace("Content-type: multipart/appledouble;[[:space:]]boundary=(.*)","",$_text);
				// ���������
				if (is_array($word)) {
					foreach ($word as $delstr)
						$_text = str_replace($delstr, "", $_text);
				}
				// �������ʸ��
				if (is_array($del_reg))
				{
					foreach ($del_reg as $delstr)
					{
						if ($delstr)
						{
							$_text = preg_replace($delstr, "", $_text);
						}
					}
				}
				
				if (strlen($_text) > $body_limit) $_text = substr($_text, 0, $body_limit)."...";
				// ��̾���
				if (preg_match("/[\s��]*(by|BY|���|�£�)(,|\.|:|\s|��)?(.{1,20}?)(<br \/>|\n|$)/",$_text,$reg_sign)){
					// ��̾��¸
					mailbbs_sign_set($from,htmlspecialchars($reg_sign[3]));
				} else { //��̾�ʤ��ξ��
					if ($_text) $_text .= mailbbs_sign_get($from);
				}
				if ($_text) {
					$type = ($sub === 'html')? 'html' : 'plain';
					$bodys[$type][] = $_text;
				}
			}
		}
		if ($write) {
			// ź�եǡ�����ǥ����ɤ�����¸
			if (eregi("Content-Transfer-Encoding:.*base64", $m_head) && eregi($subtype, $sub)) {
				$filename = '';
				// �ե�����̾�����
				if (eregi("name=\"?([^\"\n]+)\"?",$m_head, $filereg)) {
					$filename = trim($filereg[1]);
					// ���󥳡���ʸ���֤ζ������
					$filename = preg_replace("/\?=[\s]+?=\?/","?==?",$filename);
					while (eregi("(.*)=\?iso-[^\?]+\?B\?([^\?]+)\?=(.*)",$filename,$regs)) {//MIME B
						$filename = $regs[1].base64_decode($regs[2]).$regs[3];
					}
					$filename = str_replace(array('\\', '/', '?', ':', '*', '"', '<', '>', '|'), '', $filename);
					$filename = time()."-".convert($filename);
				}
				$tmp = base64_decode($m_body);
				if (!$filename) $filename = time().".$sub";
				if (strlen($tmp) < $maxbyte && !eregi($viri, $filename) && $write) {
					$fp = fopen($tmpdir.$filename, "wb");
					fputs($fp, $tmp);
					fclose($fp);
					$link = rawurlencode($filename);
					$attach = $filename;
					//����ͥ���
					$size = getimagesize($tmpdir.$filename);
					if ($size[0] > $w || $size[1] > $h) {
						thumb_create($tmpdir.$filename,$w,$h,$thumb_dir);
					}
					//��ž����
					if ($rotate)
					{
						mailbbs_rotate($filename, $rotate);
					}
				} else {
					$write = false;
					$error = 'deny_filename';
				}
			}
		}
	}
	
	$_body = (! $bodys['plain'] && $bodys['html'])? $bodys['html'] : $bodys['plain'];
	$text = join('<br /><br />', $_body);
	
	if ($write && $imgonly && !$attach) {
		$write = false;
		$error = 'no_attach';
	}
	if ($write && !$attach && !$text) {
		$write = false;
		$error = 'no_contents';
	}
	
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
		
		// �إå������Ͽ
		if ($mailbbs_head_save)
			mailbbs_head_save($id,$head);
	} else {
		// ���ݥ᡼�����¸
		if ($mailbbs_denylog_save)
			mailbbs_deny_log($head,$subject,str_replace("<br />","\n",$text),$error);
	}
}

//echo $debg;

// ������Խ���
if (count($lines) > $maxline) {
	for ($k=count($lines)-1; $k>=$maxline; $k--) {
		list($id,$tim,$sub,$fro,$tex,$at,) = explode("<>", $lines[$k]);
		if (file_exists($tmpdir.$at)) @unlink($tmpdir.$at);
		$lines[$k] = "";
	}
}
// ���˵�Ͽ
if ($write) {
	$fp = fopen($log, "wb");
	flock($fp, LOCK_EX);
	fputs($fp, implode('', $lines));
	fclose($fp);
	
	// �᡼������
	if ($notification)
	{
		include('../../mainfile.php');
		$xoopsMailer =& getMailer();
		global $xoopsConfig;
		
		$m_allow = $m_delete = $m_attach = "";
		
		if ($attach)
		{
			$m_attach =  "\nź�եե�����:\n".XOOPS_URL."/modules/mailbbs/".preg_replace("#^\./#","",$tmpdir).$filename;
			$s_name = preg_replace("/\.[^\.]+$/","",$filename).".jpg";
			if (file_exists($thumb_dir.$s_name))
			{
				$m_attach .= "\n����ͥ���:\n".XOOPS_URL."/modules/mailbbs/".preg_replace("#^\./#","",$thumb_dir).$s_name;
			}
		}
		
		if ($mailbbs_allowlog)
		{
			if ($allow)
				$m_allow = XOOPS_URL."/modules/mailbbs/?a=a&p=".md5($id.$now.$host.$user.$pass);
			else
				$m_allow = "��ǧ�Ѥ�";
			
			$m_delete = XOOPS_URL."/modules/mailbbs/?a=d&p=".md5($id.$now.$host.$user.$pass);
		}
		
		$m_url = XOOPS_URL."/modules/mailbbs/?id=".$id;
		
		$m_text = str_replace(array("<br />","&lt;","&gt;"),array("\n","<",">"),$text);
		
		$m_subject = "�̥᡼��BBS�������:ID[$id]";
		$m_body =<<<_EOD
��ǧ: $m_allow

���: $m_delete

����: $m_url

�������:
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
	// log�ե�����Υե����륹����פ򹹿�
	@touch($log);
}

if (!$img_mode){
	header("Location: $jump");
} else {
	// img�����ƤӽФ���
	header("Content-Type: image/gif");
	if ($write)
		readfile('mail.gif');
	else
		readfile('spacer.gif');
}
exit;


/* ���ޥ�ɡ���������*/
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
/* �إå�����ʸ��ʬ�䤹�� */
function mime_split($data) {
	// ���ԥ�����������
	$data = preg_replace("/(\x0D\x0A|\x0D|\x0A)/","\r\n",$data);
	$part = split("\r\n\r\n", $data, 2);
	$part[0] = ereg_replace("\r\n[\t ]+", " ", $part[0]);
	return $part;
}
/* �᡼�륢�ɥ쥹����Ф��� */
function addr_search($addr) {
	$fromreg = array();
	if (eregi("[-!#$%&\'*+\\./0-9A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+", $addr, $fromreg)) {
		return $fromreg[0];
	} else {
		return false;
	}
}
/* ʸ�������ɥ���С���auto��EUC-JP */
function convert($str,$code="EUC-JP") {
	if (function_exists('mb_convert_encoding')) {
		return mb_convert_encoding($str, $code, "auto");
	} elseif (function_exists('JcodeConvert')) {
		return JcodeConvert($str, 0, 1);
	}
	return true;
}

// ��̾��¸
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
	// ��Ͽ
	$fp = fopen($mailbbs_signs, "wb");
	flock($fp, LOCK_EX);
	fputs($fp, $signs);
	fclose($fp);
	return;
}

// ��̾����
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
// ���ݥ᡼�����¸
function mailbbs_deny_log($head,$subject,$body,$error){
	global $mailbbs_denylog;
	$subject = unhtmlentities($subject);
	$body = unhtmlentities($body);
	
	// ����������ǧ (1M�ǥ�����)
	if (@filesize($mailbbs_denylog) > 1000000)
	{
		if (!preg_match("/^(.+)(\.[^.]*)$/",$mailbbs_denylog,$match))
		{
			$match[1] = $mailbbs_denylog;
			$match[2] = "";
		}
		rename($mailbbs_denylog,$match[1].date("ymd").$match[2]);
	}
	
	// ��Ͽ
	$fp = fopen($mailbbs_denylog, "a+b");
	flock($fp, LOCK_EX);
	fputs($fp, "Error: {$error}\n\n{$head}\n\nSubject: {$subject}\n\n{$body}\n\n\n");
	fclose($fp);
	return;
}
// �إå������Ͽ
function mailbbs_head_save($id,$head){
	global $mailbbs_head_dir,$mailbbs_head_prefix;
	// ��Ͽ
	$fp = fopen($mailbbs_head_dir.$mailbbs_head_prefix.$id.".cgi", "wb");
	flock($fp, LOCK_EX);
	fputs($fp, $head);
	fclose($fp);
	return;
}

// HTML ����ƥ��ƥ��򸵤��᤹
function unhtmlentities ($string)
{
	$trans_tbl = get_html_translation_table (HTML_ENTITIES);
	$trans_tbl = array_flip ($trans_tbl);
	return strtr ($string, $trans_tbl);
}

// ���顼����
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

// ����ͥ���
function thumb_create($src, $W, $H, $thumb_dir="./")
{
	$s_file = $thumb_dir.substr($src, strrpos($src,"/")+1);
	$s_file = preg_replace("/\.[^\.]+$/",".jpg",$s_file);
	HypCommonFunc::make_thumb($src, $s_file, $W, $H);
	return;
}
?>