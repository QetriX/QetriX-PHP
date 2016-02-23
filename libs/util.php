<?php
namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.02.22 | QetriX Utils Class
 */

///

/**
 * @param $code
 * @param null $translation
 *
 * @return null|string
 */
function lbl($code, $translation = null) // TODO: Merge with lbl2
{
	if ($translation === true) return lbl2($code);
	global $lng;
	if ($translation !== null) {
		if ($lng) return $code; else return $translation;
	} else {
		if (!isset($lng) || count($lng) == 0) return $code;
		elseif (!isset($lng[$code])) return '<span onclick="if (event.ctrlKey) {window.open(\''.D.'a/g/'.$code.'/\');return false;}">-'.$code."-</span>";
		else return $lng[$code];
	}
}

/**
 * @param $code
 *
 * @return string
 */
function lbl2($code) // TODO: Merge with lbl
{
	global $lng;
	if ($lng == "") return $code;
	if (isset($lng[$code])) return $lng[$code];
	$l = explode("-", $code);
	if (count($l) == 2 && isset($lng[$l[1]])) return $lng[$l[1]];
	if (count($l) > 2) {
		if (isset($lng[$l[0]."-".$l[1]])) return $lng[$l[0]."-".$l[1]];
		elseif (isset($lng[$l[1]."-".$l[2]])) return $lng[$l[1]."-".$l[2]];
		elseif (isset($lng[$l[1]])) return $lng[$l[1]];
	}
	if (strpos($code, "_new") > 0) return lbl2(str_replace("_new", "", $code));
	return '<span onclick="if (event.ctrlKey) {window.open(\''.D.'a/g/'.$code.'/\');return false;}">-'.$code."-</span>";
}

/**
 * @param $s
 *
 * @return string
 */
function cs_utf2ascii($s)
{
	return strtr($s, array("\xc3\xa1" => "a", "\xc3\xa4" => "a", "\xc4\x8d" => "c", "\xc4\x8f" => "d", "\xc3\xa9" => "e", "\xc4\x9b" => "e", "\xc3\xad" => "i", "\xc4\xbe" => "l", "\xc4\xba" => "l", "\xc5\x88" => "n", "\xc3\xb3" => "o", "\xc3\xb6" => "o", "\xc5\x91" => "o", "\xc3\xb4" => "o", "\xc5\x99" => "r", "\xc5\x95" => "r", "\xc5\xa1" => "s", "\xc5\xa5" => "t", "\xc3\xba" => "u", "\xc5\xaf" => "u", "\xc3\xbc" => "u", "\xc5\xb1" => "u", "\xc3\xbd" => "y", "\xc5\xbe" => "z", "\xc3\x81" => "A", "\xc3\x84" => "A", "\xc4\x8c" => "C", "\xc4\x8e" => "D", "\xc3\x89" => "E", "\xc4\x9a" => "E", "\xc3\x8d" => "I", "\xc4\xbd" => "L", "\xc4\xb9" => "L", "\xc5\x87" => "N", "\xc3\x93" => "O", "\xc3\x96" => "O", "\xc5\x90" => "O", "\xc3\x94" => "O", "\xc5\x98" => "R", "\xc5\x94" => "R", "\xc5\xa0" => "S", "\xc5\xa4" => "T", "\xc3\x9a" => "U", "\xc5\xae" => "U", "\xc3\x9c" => "U", "\xc5\xb0" => "U", "\xc3\x9d" => "Y", "\xc5\xbd" => "Z"));
}

/**
 * Generates random string from 0-9a-zA-Z.
 *
 * @param int $length Length of the string
 * @param bool $safe All lookalike chars (1×I×l, 0×O...) will be removed
 * @param bool $noVwls No vowels (won't generate nasty words, like sex or porn - they contains vowels)
 *
 * @return string
 */
function genRandomString($length = 32, $safe = false, $noVwls = false)
{
	if ($safe) $chars = "23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
	else $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	if ($noVwls) $chars = str_replace(array("a", "e", "i", "o", "u", "A", "E", "I", "O", "U"), "", $chars);
	$str = "";
	for ($p = 0; $p < $length; $p++) $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	return $str;
}


/**
 * Redirects page to another page
 *
 * @param string $url URL, or part of URL, for redirect
 * @param bool $force Redirect, even if output has been sent already
 * @param string $title Optional debug title
 */

function go($url = "", $force = false, $title = null)
{
	if ($url === null) return;
	if (strpos($url, "://") === false) $url = "http://".$_SERVER["SERVER_NAME"].D.str_replace("&amp;", "&", $url); // TODO: There's no "D" constant
	if (ob_get_length()) {
		if ($force) {
			echo "<script type=\"text/javascript\">";
			flush();
			//if ($force == 2) echo "alert("Redirecting to: ".$url."");";flush();
			echo "location.href='".$url."';";
			flush();
			echo "</script>";
			flush();
		} else {
			echo '<div style="font-family:tahoma,sans serif;margin-top:10px;">Redirect: <a id="link" href="'.$url.'">'.$url.' &gt;&gt;</a></div><script type="text/javascript">document.getElementById(\'link\').focus();</script>';
			if ($title !== null) echo " - ".$title;
		}
		die();
	}
	//header('HTTP/1.1 301 Moved Permanently');
	header("Location: ".$url);
	die();
}

class Util
{
	/**
	 * Irreversibly rewrite, strip, decode and transliterate unicode string to url-friendly lowercase ASCII string
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function crKey($text)
	{
		$return = str_replace(
			array("ε", "λ", "η", "ν", "ι", "κ", "ή", "δ", "μ", "ο", "ρ", "α", "τ", "ί", "Ε", "Λ", "Η", "Ν", "Ι", "Κ", "Ή", "Δ", "Μ", "Ο", "Ρ", "Α", "Τ", "Ί",
				"ж", "ч", "щ", "ш", "ю", "а", "б", "в", "г", "д", "e", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ъ", "ь", "я",
				"Ж", "Ч", "Щ", "Ш", "Ю", "А", "Б", "В", "Г", "Д", "Е", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ъ", "Ь", "Я",
				"=", "!", ":", ";", " ", "+", "&", "_", "/", "@", "?", "!", ".", ",", "–", "½", "¾",
				"æ", "à", "á", "â", "ã", "ä", "å", "ç", "č", "ď", "è", "é", "ě", "ê", "ë", "ì", "í", "î", "ï", "ñ", "ň", "ò", "ó", "ô", "õ", "ö", "ø", "ō", "ř", "š", "ť", "ù", "ú", "ů", "û", "ü", "ý", "ÿ", "ž",
				"À", "Á", "Â", "Ã", "Ä", "Å", "Ç", "Č", "Ď", "È", "É", "Ě", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ñ", "Ň", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ō", "Ř", "Š", "Ť", "Ù", "Ú", "Ů", "Û", "Ü", "Ý", "Ž",
				"×"),
			array("e", "l", "i", "n", "i", "k", "i", "d", "m", "o", "r", "a", "t", "i", "e", "l", "i", "n", "i", "k", "i", "d", "m", "o", "r", "a", "t", "i",
				"zh", "ch", "sht", "sh", "yu", "a", "b", "v", "g", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "h", "c", "y", "x", "q",
				"zh", "ch", "sht", "sh", "yu", "a", "b", "v", "g", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "h", "c", "y", "x", "q",
				"-", "-", "-", "-", "-", "-", "-", "-", "-", "-at-", "-", "-", "-", "-", "-", "-1-2", "-3-4",
				"ae", "a", "a", "a", "a", "a", "a", "c", "c", "d", "e", "e", "e", "e", "e", "i", "i", "i", "i", "n", "n", "o", "o", "o", "o", "o", "o", "o", "r", "s", "t", "u", "u", "u", "u", "u", "y", "y", "z",
				"a", "a", "a", "a", "a", "a", "c", "c", "d", "e", "e", "e", "e", "e", "i", "i", "i", "i", "n", "n", "o", "o", "o", "o", "o", "o", "o", "r", "s", "t", "u", "u", "u", "u", "u", "y", "z",
				"x"),
			str_replace(array("\"", "'", "°", "„", "“", "`", "ʻ", "*", "¨", "™", "®", "§", "(", ")", "{", "}", "<", ">"), "", trim($text)));
		$return = str_replace("--", "-", str_replace("--", "-", str_replace("---", "-", $return))); // Fix multiple dashes
		$return = strToLower(preg_replace('/[[:^print:]]/', '', $return));
		if (substr($return, -1) == "-") $return = substr($return, 0, -1);
		if (substr($return, 0, 1) == "-") $return = substr($return, 1); // Strip a dash from beginning and/or end of the string
		return $return;
	}

	public static function crc32($data)
	{
		static $map = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$hash = bcadd(sprintf("%u", crc32($data)), 0x100000000);
		$str = "";
		do {
			$str = $map[bcmod($hash, 62)].$str;
			$hash = bcdiv($hash, 62);
		} while ($hash >= 1);
		return $str;
	}

	public static function isNumeric($str)
	{
		return is_numeric($str);
	}

	public static function log($value, $title = null)
	{
		// TODO: This MUST be in a renderer!!! Also Messager!!!
		echo "<div style=\"clear:both;text-align:left;width:720px;margin:5px auto;background:#fff;color:#333;position:relative;z-index:9999;padding:15px;font-size:1em;\">";
		if ($title !== null) echo "<pre>-------------------- <h3 style=\"color:#00a;display:inline;clear:none;float:none;margin:0;padding:0;\">".$title."</h3> --------------------</pre>";
		var_dump($value);
		echo "</div>";
	}

	/** Parse settings string to associative array
	 *
	 * @param $str
	 * @param string $delimiter
	 * @param array $data
	 *
	 * @return array|null
	 */
	public static function getSettings($str, $delimiter = " ", array $data = null) // , $delimiter='\ ', $noValue=''
	{
		preg_match_all("/([^".$delimiter.":]+)(:[^".$delimiter."]*)?\\".$delimiter."/", trim($str).$delimiter, $matches, PREG_SET_ORDER); // TODO: is using str_replace the only way?
		if ($matches == array()) return null;

		$out = array();
		foreach ($matches as $match) {
			$val = isset($match[2]) ? ($match[2] == ":" ? "" : substr($match[2], 1)) : $match[1];
			$out[$match[1]] = ($data !== null && strPos($val, "%") !== false ? Util::processVars($val, $data) : $val);
		}
		return $out;
	}

	/**
	 * processVars
	 *
	 * @param string $str String with %vars%
	 * @param array $data Data for vars, keys must match with vars
	 *
	 * @return string
	 */
	public static function processVars($str, array $data)
	{
		// If no variables or no data, return the original string (no point of parsing it)
		if (strpos($str, "%") === false || count($data) == 0) return $str;

		$xx = explode("%", $str);
		$strx = "";
		foreach ($xx as $x) $strx .= (isset($data[$x]) ? $data[$x] : $x);
		return $strx;
	}

	/** Ceil up to nearest ten, hundread... */
	public static function ceil($value, $precision)
	{
		$pow = pow(10, $precision);
		return (ceil($pow * $value) + ceil($pow * $value - ceil($pow * $value))) / $pow;
	}


	/** Returns class name */
	public static function getClassName($className)
	{
		if (is_object($className)) $className = get_class($className);
		if ($pos = strrpos($className, '\\')) return strToLower(substr($className, $pos + 1));
		return strToLower($className);
	}

	public static function password($password)
	{
		if (defined("CRYPT_BLOWFISH") && CRYPT_BLOWFISH) {
			$salt = "$2x$9d$".substr(md5(uniqid(rand(), true)), 0, 22);
			return crypt($password, $salt);
		} else {
			$intermediateSalt = md5(uniqid(rand(), true));
			$salt = substr($intermediateSalt, 0, 8);
			return hash("sha256", $password.$salt);
		}
	}


	/**
	 * Pimped up number_format()
	 * Works for numbers from 0.0001 to PHP_INT_MAX
	 *
	 * @param int $num Number to format
	 * @param int $maxdec = (2): 3.0 => 3, 3.125 => 3.13, 3.100 => 3.1
	 *
	 * @return int|string
	 */
	public static function formatNumber($num, $maxdec = 2)
	{
		if (!is_numeric($num)) return $num;
		if (strpos($num, ".") !== false) {
			$x = ($num - round($num))."";
			if (abs($x) > 0.0001) { /// bypasses error in rounding
				if (strlen(abs($x)) - 2 < $maxdec) $maxdec = strlen(abs($x)) - 2;
				for ($i = strpos($num, "."); $i < $maxdec + 1; $i++) {
					if (!isset($x[$i + 2]) || ($x[$i + 2] == "0" && ($x[$i + 3] == "0" || !isset($x[$i + 3])))) {
						$maxdec = $i;
						break;
					}
				}
			} else $x = 0;
			$numx = number_format($num, $maxdec, ".", " ");
			if (substr($numx, -1) == "0") $numx = substr($numx, 0, -1);
		} else $numx = number_format($num, 0, ".", " ");
		return $numx;
	}


	/**
	 * Converts date in QetriX DateTime Format to datetime formatted string
	 *
	 * @param $str
	 * @param string $format
	 *
	 * @internal param $hr
	 * @return string
	 */
	public static function formatDateTime($str, $format = '%3$d.%2$d.%1$d %4$02d:%5$02d')
	{
		/*
		* 201100000000 = year 2011 (2011)
		* 201100100000 = 1st half of 2011
		* 201100010000 = 1st quarter of 2011 (I/2011)
		* 201110000000 = October 2011 (=> 201110) (10/2010)
		* 201101000000 = January 2011 (01/2010)
		* 201101100000 = January 10, 2011 (10.1.2011)
		 */
		if ($str == "") return "";
		$str = substr($str."00000000000000", 0, 14);
		$s = substr($str, -2);
		$str = substr($str, 0, -2);
		$m = substr($str, -2);
		$str = substr($str, 0, -2);
		$h = substr($str, -2);
		$str = substr($str, 0, -2);
		$d = substr($str, -2);
		$str = substr($str, 0, -2);
		$M = substr($str, -2);
		$str = substr($str, 0, -2);
		$y = $str;

		if ($M + 0 == 0 && $d + 0 == 0) return $y;
		elseif ($M + 0 > 0 && $d + 0 == 0) return $M."/".$y;
		elseif ($M + 0 == 0 && $d + 0 > 0) {
			$arr = array(null, "I", "II", "III", "IV");
			return $arr[$d + 0]."/".$y;
		}

		return sprintf($format, $y, $M, $d, $h, $m, $s);
	}

	public static function subvalSort($a, $subkey)
	{
		if ($a == array()) return false;
		foreach ($a as $k => $v) $b[$k] = strToLower($v[$subkey]);
		asort($b);
		$c = array();
		foreach ($b as $key => $val) $c[] = $a[$key];
		return $c;
	}

	public static function urlEncode($str)
	{
		return str_replace(array("%21"), array("!"), rawUrlEncode($str));
	}

	public static function urlDecode($str)
	{
		return rawUrlDecode($str);
	}

	public static function base64Encode($str)
	{
		return strtr(base64_encode($str), '+/=', '-_,');
	}

	public static function base64Decode($str)
	{
		return base64_decode(strtr($str, '-_,', '+/='));
	}

	/**
	 * Generates Universal Unique ID ("UUID" or "GUID")
	 * @return string
	 */
	public static function uuid($compressed = false)
	{

		return sprintf($compressed ? "%04x%04x%04x%04x%04x%04x%04x%04x" : "%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
	}

	/**
	 * Send text (=non-HTML) UTF8 mail
	 *
	 * @param $rcpt
	 * @param $subject
	 * @param $text
	 * @param string $from
	 * @param string $headers
	 *
	 * @return bool
	 */
	public static function sendTextMail($rcpt, $subject, $text, $from = "", $headers = "")
	{
		if ($rcpt != "") {
			echo "1";
			$headers = cs_utf2ascii($headers);
			$subject = cs_utf2ascii($subject);
			if ($from == "") $from = "qetrix@".$_SERVER["HTTP_HOST"];
			if ($GLOBALS["online"] && MODE != QState::Dev) { // TODO
				// echo $rcpt.'<br />'.$text;
				return mail($rcpt, $subject, $text, "Content-Type: text/plain; charset=utf-8\nFrom: ".$from."\n".$headers);
			} else {
				echo "<strong>SENDMAIL</strong>: ".$rcpt."<br />";
				echo "".$subject."<hr />";
				echo "<pre>".$text."</pre><hr />".$headers;
				return true;
			}
		}
		return false;
	}

	/*
	 * convert(obj); => Convert obj from type(obj) to defFormat
	 * convert(str, b); => Convert str from b to defFormat
	 * convert(obj, b); => Convert obj from type(obj) to defFormat.b
	 * convert(obj, b, c); => Convert obj from type(obj) to format b.c
	 * convert(str, b, c); => Convert str from type b to format c
	 * convert(str, b, c, d); => Convert str from type b to c.d
	*/
	public static function convert($data, $arg1 = null, $arg2 = null, $arg3 = null, $args = array())
	{
		$fromFormat = null;
		$toFormat = QApp::getInstance()->outputFormat();
		$toType = "";

		if ($data === null) throw new \Exception("Error: null is not convertible."); // TODO: Maybe debug only? May return empty string.

		// Data as Object
		if (is_object($data)) {
			$fromFormat = Util::getClassName($data);
			if ($arg2 === null) { // Convert(obj, p1)
				$toType = $arg1;
			} elseif ($arg3 === null) { // Convert(obj, p1, p2)
				$toFormat = $arg1;
				$toType = $arg2;
			} else {
				throw new \Exception("Error: Convert for ".$fromFormat." cannot define from_format, becuase it's derived from it's type.");
			}

			// Data as String
		} elseif (is_string($data)) {
			$data = trim($data);

			if ($arg2 === null) { // Convert("str", p1)
				$fromFormat = $arg1;
			} elseif ($arg3 === null) { // Convert("str", p1, p2)
				$fromFormat = $arg1;
				$toFormat = $arg2;
			} else { // Convert("str", p1, p2, p3)
				$fromFormat = $arg1;
				$toFormat = $arg2;
				$toType = $arg3;
			}

			// Data as Array
		} elseif (is_array($data)) {
			$fromFormat = count($data) == 0 || (isset($data[0]) && isset($data[count($data) - 1])) ? "array" : "hashmap";
			$toFormat = $arg1;
		}

		$convPath = "converters/".strToLower($fromFormat."_".$toFormat.($toType != "" ? "_".$toType : "")).".php";
		$path = file_exists(QApp::getInstance()->pathApp().$convPath) ? QApp::getInstance()->pathApp() : QApp::getInstance()->pathAppCommon();
		if (!file_exists($path.$convPath)) throw new \Exception("Error: Convertor ".$convPath." not found!"); // TODO: Disable for PROD for performance
		include_once $path.$convPath;
		$convClass = "com\\qetrix\\apps\\".($path == QApp::getInstance()->pathApp() ? QApp::getInstance()->name() : "common")."\\converters\\".$fromFormat."_".$toFormat.($toType != "" ? "_".$toType : "");
		return (new $convClass())->convert($data, $args);
	}
}


/**
 * Replacement of file_get_contents() for reading from external source (HTTP), using cURL
 *
 * @param string $url URL of source
 * @param string $access Format: username:password
 *
 * @return mixed
 */
function urlGetContents($url, $access = null)
{
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	if ($access !== null) {
		curl_setopt($curl, CURLOPT_USERPWD, $access);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	}
	//$info = curl_getinfo($ch);
	$result = curl_exec($curl);
	curl_close($curl);
	return $result;
}

/**
 * TODO: Why hr?!?
 *
 * @param $hr
 *
 * @return string
 */
function dhms($hr)
{
	if ($hr > 0) {
		$days = ceil($hr / 24);
		if ($days > 1) {
			return "".floor($days - 1)."d ".($hr % 24)."h";
		} else {
			$min = $hr * 60;
			if ($min >= 1) {
				$hr = floor($min / 60);
				return ($hr < 1 ? "" : floor($hr)."h ").substr(($hr >= 1 ? "0" : "").floor(($min / 60 - $hr) * 60), -2)."m";
			} else return ceil($min * 60)."s";
		}
	} else return ceil($hr * 60)."m";
}

/**
 * Print hours and minutes from seconds
 *
 * @param $sec
 *
 * @return string
 */
function hm($sec)
{
	if ($sec === null || $sec === "") return false;
	$h = floor($sec / 3600);
	$m = floor(($sec % 3600) / 60);
	return $h."h ".substr("00".$m, -2)."m";
}

/** Set Navigation Link for QList (see some custom MySQL DataStores) */
function setNavLink($path, $pagelink, $action, $text, array $items = null)
{
	// Util::log($action, $pagelink);
	$item = array();
	if ($text !== null) $item["text"] = $text;
	if ($pagelink != $action && $action !== null) {
		$item["action"] = (strpos($action, "://") === false ? $path : '').$action;
		if (substr($pagelink, 0, strlen($action)) == $action) $item["selected"] = true;
	}
	if ($items !== null) {
		$item["items"] = array();
		foreach ($items as $i) $item["items"][] = setNavLink($path, $pagelink, $i["action"], $i["text"], isset($i["items"]) ? $i["items"] : null);
	}
	if ($action === null && $text === null) return $item["items"];
	return $item;
}
