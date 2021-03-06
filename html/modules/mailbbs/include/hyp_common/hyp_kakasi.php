<?php
// $Id: hyp_kakasi.php,v 1.1 2006/06/22 06:33:52 nao-pon Exp $
// Hyp_KAKASI Class by nao-pon http://hypweb.net
////////////////////////////////////////////////

if( ! class_exists( 'Hyp_KAKASHI' ) )
{
class Hyp_KAKASHI
{
	// 基本設定
	var $kakasi_path = "/usr/bin/kakasi";    // KAKASI のパス
	
	var $encoding = "";       // 文字コード(現状はEUC-JP専用のため使用せず)
	
	var $tmp_dir = "";        // 分かち書き用キャッシュ保存用ディレクトリ
	var $gc_probability = 1;  // gc処理する確率 x  x/1000の確率で処理
	var $cache_expire = 24;   // キャッシュの有効期限(h)
	
	// 内部変数
	var $dicts = array();
	var $cmd = "";
	
	function Hyp_KAKASHI()
	{
		//$this->encoding = _CHARSET ;
		//$this->tmp_dir = XOOPS_ROOT_PATH . "/cache2/kakasi/";
	}
	
	function add_dict($dict)
	{
		if (is_file($dict))
		{
			array_push($this->dicts,$dict);
		}
	}
	
	function get_wakatigaki(&$str)
	{
		if (!$this->kakasi_path) return false;
		
		if ($this->tmp_dir)
		{
			// gc(ガベージコレクト)
			if (mt_rand(1,100000) <= $this->gc_probability * 100)
			{
				if ($handle = opendir($this->tmp_dir))
				{
					while (false !== ($file = readdir($handle)))
					{
						if (strpos($file,".") === 0) continue;
						if (filemtime($this->tmp_dir.$file) < time() - $this->cache_expire * 3600)
						{
							unlink($this->tmp_dir.$file);
						}
					}
					closedir($handle); 
				}
			}
			
			$tmpfile = $this->tmp_dir.md5($str).".tmp";
			
			// キャッシュ
			if (file_exists($tmpfile))
			{
				touch($tmpfile);
				$str = join("",file($tmpfile));
				return true;
			}
		}
		
		$put = $str;
		$nwa = "";
		$match = array();
		if (preg_match_all("/((\"|'|”|’).+?(?:\\2))/",$put,$match,PREG_PATTERN_ORDER))
		{
			$match[1] = array_unique($match[1]);
			foreach($match[1] as $rep)
			{
				$put = str_replace($rep," ",$put);
			}
			
			$put = preg_replace("/ +/"," ",$put);
			$nwa = join(" ",$match[1])." ";
		}
		
		if (!$this->execute($put, "-w -c")) return false;
		
		$str = $nwa.$put;
		
		if ($this->tmp_dir)
		{
			if ($fp = fopen($tmpfile, "wb"))
			{
				fputs($fp, $str);
				fclose($fp);
			}
		}
		
		return true;
	}
	
	function get_keyword(&$str, $limit=10, $minlen=3, $minpoint=2)
	{
		$str = preg_replace("/[ \t]+/"," ",$str);
		$keys = array();
		$_dat = "";
		
		foreach (preg_split("/[\r\n]+/",$str) as $_str)
		{
			if ((strlen($_dat)+strlen($_str)) > 10000)
			{
				$_dat = substr($_dat, 0, 10000);
				if (!$this->get_wakatigaki($_dat))
				{
					$str = "";
					return false;
				}
				$keys = array_merge($keys,explode(" ", $_dat));
				$_dat = "";
			}
			else
			{
				$_dat .= $_str;
			}
		}
		if ($_dat)
		{
			if (!$this->get_wakatigaki($_dat))
			{
				$str = "";
				return false;
			}
			$keys = array_merge($keys,explode(" ", $_dat));			
		}
		rsort($keys);
		
		$arr = array();
		foreach ($keys as $key)
		{
			if (strlen($key) < $minlen) continue;
			if (isset($arr[$key]))
			{
				$arr[$key]++;
			}
			else
			{
				$arr[$key] = 1;
			}
		}
		arsort($arr);
		$ret = array_splice($arr, 0 , min($limit, count($arr)));
		if (count($ret) > 0)
		{
			$_ret = array();
			foreach ($ret as $_key=>$_cnt)
			{
				if ($_cnt < $minpoint) continue;
				if ($_key) $_ret[] = $_key;
			}
			$str = join(' ', $_ret);
		}
		else
		{
			$str = "";
		}
		return true;
	}
	
	function get_katakana(&$str)
	{
		return $this->execute($str, "-kK -HK -JK");
	}

	function get_hiragana(&$str)
	{
		return $this->execute($str, "-kH -KH -JH");
	}
	
	function get_roma(&$str)
	{
		return $this->execute($str, "-Ha -Ka -Ja -Ea -ka");
	}
	
	function get_expert(&$str,&$cmd)
	{
		$cmd = trim($cmd);
		$reg = "/^\-(a[jE]|j[aE]|g[ajE]|k[ajKH]|E[aj]|H[ajkK]|K[ajkH]|J[ajkHK]|[pfscCUw]|r[hk]?)$/";
		$_tmp = array();
		foreach(explode(" ",$cmd) as $_cmd)
		{
			if (preg_match($reg, $_cmd))
			{
				$_tmp[] = $_cmd;
			}
		}
		$cmd = join(" ",$_tmp);
		if (!$cmd) return false;
		return $this->execute($str, $cmd);
	}

	function execute(&$str,$cmd)
	{
		$ret = false;
		$dic = "";
		$cmd = " -ieuc ".$cmd;
		if (is_file($this->kakasi_path) && (function_exists('is_executable'))? is_executable($this->kakasi_path) : 1)
		{
			if ($this->dicts)
			{
				$dic = " ".join(", ",$this->dicts);
			}
			$pipes = array();
			$descriptorspec = array(
				0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
				1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			);
			$this->cmd = $cmd.$dic;
			$process = proc_open($this->kakasi_path.$this->cmd, $descriptorspec, $pipes);
			if (is_resource($process))
			{
				fputs($pipes[0], $str);
				fclose($pipes[0]);
				$rstr = $_str = "";
				while($_str = fgets ($pipes[1]))
				{
					$rstr .= $_str;
				}
				if ($rstr)
				{
					$str = $rstr;
					$ret = true;
				}
				fclose($pipes[1]);
				proc_close($process);
			}
		}
		return $ret;
	}
}
}
?>