<?php declare(strict_types = 1);
namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 17.06.19 | QetriX Utils Class
 */

function x()
{
}

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
function lbl2($code) // TODO: Merge with lblQB
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


final class Util
{
	/** Use Util::toPath instead
	 * @deprecated
	 */
	public static function crKey($useToPathInstead, $wsp = "-")
	{
		return Util::toPath($useToPathInstead, $wsp);
	}
	/**
	 * Irreversibly rewrites, strips, decodes and transliterates unicode string to path-friendly lowercase ASCII string
	 *
	 * @param string $text
	 * @param string $wsp Word separator character (= space)
	 *
	 * @return string
	 */
	public static function toPath($text, $wsp = "-")
	{
		$return = str_replace(
			["ε", "λ", "η", "ν", "ι", "κ", "ή", "δ", "μ", "ο", "ρ", "α", "τ", "ί", "Ε", "Λ", "Η", "Ν", "Ι", "Κ", "Ή", "Δ", "Μ", "Ο", "Ρ", "Α", "Τ", "Ί",
				"ж", "ч", "щ", "ш", "ю", "а", "б", "в", "г", "д", "e", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ъ", "ь", "я",
				"Ж", "Ч", "Щ", "Ш", "Ю", "А", "Б", "В", "Г", "Д", "Е", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ъ", "Ь", "Я",
				"=", "!", ":", ";", " ", "+", "&", "_", "/", "@", "?", "!", ".", ",", "–", "½", "¾",
				"æ", "à", "á", "â", "ã", "ä", "å", "ç", "č", "ď", "è", "é", "ě", "ê", "ë", "ì", "í", "î", "ï", "ĺ", "ñ", "ň", "ò", "ó", "ô", "õ", "ö", "ø", "ō", "ŕ", "ř", "š", "ť", "ù", "ú", "ů", "û", "ü", "ý", "ÿ", "ž",
				"À", "Á", "Â", "Ã", "Ä", "Å", "Ç", "Č", "Ď", "È", "É", "Ě", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ĺ", "Ñ", "Ň", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ō", "Ŕ", "Ř", "Š", "Ť", "Ù", "Ú", "Ů", "Û", "Ü", "Ý", "Ž",
				"×"],
			["e", "l", "i", "n", "i", "k", "i", "d", "m", "o", "r", "a", "t", "i", "e", "l", "i", "n", "i", "k", "i", "d", "m", "o", "r", "a", "t", "i",
				"zh", "ch", "sht", "sh", "yu", "a", "b", "v", "g", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "h", "c", "y", "x", "q",
				"zh", "ch", "sht", "sh", "yu", "a", "b", "v", "g", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "h", "c", "y", "x", "q",
				$wsp, $wsp, $wsp, $wsp, $wsp, $wsp, $wsp, $wsp, $wsp, $wsp."at".$wsp, $wsp, $wsp, $wsp, $wsp, $wsp, $wsp."1".$wsp."2", $wsp."3".$wsp."4",
				"ae", "a", "a", "a", "a", "a", "a", "c", "c", "d", "e", "e", "e", "e", "e", "i", "i", "i", "i", "i", "n", "n", "o", "o", "o", "o", "o", "o", "o", "r", "r", "s", "t", "u", "u", "u", "u", "u", "y", "y", "z",
				"a", "a", "a", "a", "a", "a", "c", "c", "d", "e", "e", "e", "e", "e", "i", "i", "i", "i", "i", "n", "n", "o", "o", "o", "o", "o", "o", "o", "r", "r", "s", "t", "u", "u", "u", "u", "u", "y", "z",
				"x"],
			str_replace(["\"", "'", "°", "„", "“", "`", "ʻ", "*", "¨", "™", "®", "§", "(", ")", "{", "}", "<", ">"], "", trim($text)));
		$return = strToLower(preg_replace("/[[:^print:]]/", "", $return));
		$return = str_replace($wsp.$wsp, $wsp, str_replace($wsp.$wsp, $wsp, str_replace($wsp.$wsp.$wsp, $wsp, $return))); // Fix multiple dashes
		if (substr($return, -1) == $wsp) $return = substr($return, 0, -1);
		if (substr($return, 0, 1) == $wsp) $return = substr($return, 1); // Strip a dash from beginning and/or end of the string
		return $return;
	}

	/** FIXME: Every online validator gives different CRC number!!! */
	public static function crc32(string $data)
	{
		static $map = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$hash = bcadd(sprintf("%u", crc32($data)), "0x100000000");
		$str = "";
		do {
			$str = $map[bcmod($hash, "62")].$str;
			$hash = bcdiv($hash, "62");
		} while ($hash >= 1);
		return $str;
	}

	public static function startsWith($haystack, $needle, $ignoreCase = true)
	{
		return $ignoreCase ? strToLower(substr($haystack, 0, strlen($needle))) == strToLower($needle) : substr($haystack, 0, strlen($needle)) == $needle;
	}

	public static function endsWith($haystack, $needle, $ignoreCase = true)
	{
		return $ignoreCase ? strToLower(substr($haystack, -strlen($needle))) == strToLower($needle) : substr($haystack, -strlen($needle)) == $needle;
	}

	public static function isNumeric($value)
	{
		return is_numeric($value);
	}

	public static function isActionPath($value)
	{
		return strpos($value, "/") !== false && strpos($value, "\t") === false;
	}

	public static function log($value = "\t\t", $title = "")
	{
		if ($value === "\t\t") $value = time();
		// TODO FIXME: This MUST be in a renderer!!! Also Messager!!!
		$btr = debug_backtrace(0);

		//echo "<pre style=\"text-align:left;font-size: 13px;line-height:12px;width:1000px;margin: 1px auto;overflow: hidden;border:2px solid red;background: #fcc;padding:20px;\">this: ";print_r($tree);echo "</pre>";

		echo "<div style=\"clear:both;text-align:left;width:720px;margin:5px auto;background:#fff;color:#333;position:relative;z-index:9999;padding:15px;font-size:14px;\"><pre>";
		if ($title."" != "") echo "-------------------- <h3 style=\"color:#00a;display:inline;clear:none;float:none;margin:0;padding:0;\">".$title."</h3> --------------------\n";
		var_dump($value);
		echo "</pre><div style=\"font-size:0.9em;color:#999;font-family:monospace;\">".$btr[0]["file"].":".$btr[0]["line"].(isset($btr[1]) ? " (".$btr[1]["function"].")" : "")."</div>";
		echo "</div>";
	}

	/** Parse settings string to associative array
	 *
	 * @param $line
	 * @param string $delimiter
	 * @param array $data
	 *
	 * @return array|null
	 */
	public static function getQueRow($line, $delimiter = " ", array $data = null) // , $delimiter='\ ', $noValue=''
	{
		preg_match_all("/([^".$delimiter.":]+)(:[^".$delimiter."]*)?\\".$delimiter."/", trim($line).$delimiter, $matches, PREG_SET_ORDER); // TODO: is using str_replace the only way?
		if (count($matches) == 0) return null;

		$arr = [];
		foreach ($matches as $match) {
			$val = isset($match[2]) ? ($match[2] == ":" ? "" : substr($match[2], 1)) : $match[1];
			$arr[$match[1]] = ($data !== null && strPos($val, "%") !== false ? Util::processVars($val, $data) : $val);
		}
		return $arr;
	}

	/**
	 * processVars
	 *
	 * @param string $str String with %vars%
	 * @param array $data Data for vars, keys must match with vars
	 *
	 * @return string Processed string
	 *
	 * TODO: Instead using %xxx% for vars, rewrite it to using {{xxx}}
	 */
	public static function processVars($str, array $data)
	{
		/// If no variables or no data, return the original string (no point of parsing it)
		if (strpos($str, "%") === false || count($data) == 0) return $str;

		$xx = explode("%", $str);
		$strx = "";
		foreach ($xx as $x) $strx .= (array_key_exists($x, $data) ? $data[$x] : $x);
		return $strx;
	}

	/** Ceil up to nearest ten, hundread...
	 *
	 * @param float|double $value
	 * @param float|int $significance
	 *
	 * @return float
	 */
	public static function ceil($value, $significance = 1)
	{
		return ceil($value / $significance) * $significance;
	}

	public static function getArrayType(array $arr)
	{
		if (!is_array($arr)) return -1; // Not array
		if (count($arr) == 0) return 0; // Unknown

		end($arr);
		$key = key($arr);
		reset($arr); // Find last key
		if (isset($arr[0]) && count($arr) - 1 == $key) {
			if (is_array($arr[0])) return isset($arr[0][0]) ? 3 : 4; // List of lists : List of Maps
			else return 1; //"List";
		} elseif (is_array($arr[array_keys($arr)[0]])) return 4; // List of Maps
		return 2; // Map
	}


	/**
	 * @param $s
	 *
	 * @return string
	 */
	public static function cs_utf2ascii($s)
	{
		return strtr($s, array("\xc3\xa1" => "a", "\xc3\xa4" => "a", "\xc4\x8d" => "c", "\xc4\x8f" => "d", "\xc3\xa9" => "e", "\xc4\x9b" => "e", "\xc3\xad" => "i", "\xc4\xbe" => "l", "\xc4\xba" => "l", "\xc5\x88" => "n", "\xc3\xb3" => "o", "\xc3\xb6" => "o", "\xc5\x91" => "o", "\xc3\xb4" => "o", "\xc5\x99" => "r", "\xc5\x95" => "r", "\xc5\xa1" => "s", "\xc5\xa5" => "t", "\xc3\xba" => "u", "\xc5\xaf" => "u", "\xc3\xbc" => "u", "\xc5\xb1" => "u", "\xc3\xbd" => "y", "\xc5\xbe" => "z", "\xc3\x81" => "A", "\xc3\x84" => "A", "\xc4\x8c" => "C", "\xc4\x8e" => "D", "\xc3\x89" => "E", "\xc4\x9a" => "E", "\xc3\x8d" => "I", "\xc4\xbd" => "L", "\xc4\xb9" => "L", "\xc5\x87" => "N", "\xc3\x93" => "O", "\xc3\x96" => "O", "\xc5\x90" => "O", "\xc3\x94" => "O", "\xc5\x98" => "R", "\xc5\x94" => "R", "\xc5\xa0" => "S", "\xc5\xa4" => "T", "\xc3\x9a" => "U", "\xc5\xae" => "U", "\xc3\x9c" => "U", "\xc5\xb0" => "U", "\xc3\x9d" => "Y", "\xc5\xbd" => "Z"));
	}

	/**
	 * Generates random string from 0-9a-zA-Z.
	 *
	 * @param int $length Length of the string
	 * @param int $groups 3-bit number (1-7), 1st bit is numbers, 2nd bit is lower case letters, 3rd bit is upper case letters. Default is 7 = everything.
	 * @param int $safeLevel 2-bit number (0-3), 1st bit is to remove all lookalikes (1×I×l, 0×O...), 2nd bit is to remove all vowels (prevention of accidental nasty words). Default is 0 = no safety.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function getRandomString($length = 32, int $groups = 7, int $safeLevel = 0)
	{
		if ($groups < 1 || $groups > 7) throw new \Exception("\"groups\" has to be between 1 and 7, ".$groups." given.");
		list($num, $loc, $upc) = str_split(decbin($groups));

		$chars = "";
		if ($num) $chars .= "0123456789";
		if ($loc) $chars .= "abcdefghijklmnopqrstuvwxyz";
		if ($upc) $chars .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

		if ($safeLevel == 1 || $safeLevel == 3) $chars = str_replace(["0", "O", "o", "1", "l", "I"], "", $chars); // Remove all lookalike chars (1×I×l, 0×O...)
		if ($safeLevel == 2 || $safeLevel == 3) $chars = str_replace(["a", "e", "i", "o", "u", "A", "E", "I", "O", "U"], "", $chars); // No vowels (won't generate nasty words, like sex or porn - they contains vowels)

		$str = "";
		for ($p = 0; $p < $length; $p++) $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		return $str;
	}

	/** Returns class name */
	public static function getClassName($className)
	{
		if (is_object($className)) $className = get_class($className);
		if ($pos = strrpos($className, "\\")) return strToLower(substr($className, $pos + 1));
		return strToLower($className);
	}

	/** Returns method name
	 *
	 * @param string $methodName Method name, often provided by __METHOD__ magic constant
	 *
	 * @return string
	 */
	public static function getMethodName($methodName)
	{
		return strToLower(explode("::", $methodName)[1]);
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

	public static function parseTime($time)
	{
		$t = explode(":", str_replace(" ", "", $time));
		return ($t[0] * 3600) + ($t[1] * 60) + (isset($t[2]) ? $t[2] : 0);
	}


	/**
	 * Pimped up number_format()
	 * Works for numbers from 0.0001 to PHP_INT_MAX
	 *
	 * @param int|float $num Number to format
	 * @param int $maxdec = (2): 3.0 => 3, 3.125 => 3.13, 3.100 => 3.1
	 *
	 * @return int|string
	 */
	public static function formatNumber($num, $maxdec = 2)
	{
		if (!is_numeric($num)) return $num;
		if (strpos($num."", ".") !== false) {
			$x = ($num - round($num))."";
			if (abs($x) > 0.0001) { /// bypasses error in rounding
				if (strlen(abs($x)."") - 2 < $maxdec) $maxdec = strlen(abs($x)."") - 2;
				for ($i = strpos($num."", "."); $i < $maxdec + 1; $i++) {
					if (!isset($x[$i + 2]) || ($x[$i + 2] == "0" && ($x[$i + 3] == "0" || !isset($x[$i + 3])))) {
						$maxdec = $i;
						break;
					}
				}
			} else $x = 0;
			$numx = number_format($num + 0, $maxdec, ".", " ");
			//if (substr($numx, -1) == "0") $numx = substr($numx, 0, -1);
		} else $numx = number_format($num + 0, 0, ".", " ");
		return $numx;
	}


	/**
	 * Converts date in QetriX DateTime Format to datetime formatted string
	 *
	 * @param string $dtstr Date
	 * @param string $format Date format
	 *
	 * @internal param $hr
	 * @return string
	 */
	public static function formatDateTime($dtstr, $format = "%3\$d.%2\$d.%1\$d %4\$02d:%5\$02d")
	{
		/*
		* 201100000000 = year 2011 (2011)
		* 201100010000 = 1st quarter of 2011 (I/2011)
		* 201110000000 = October 2011 (=> 201110) (10/2010)
		* 201101000000 = January 2011 (01/2010)
		* 201101100000 = January 10, 2011 (10.1.2011)
		 */
		if ($dtstr == "") return "";
		$dtstr = substr(str_replace(["-", ".", "/", " ", ":"], "", $dtstr)."00000000000000", 0, 14); // Replace allows direct use of yyyy-mm-dd dates (MySQL)
		$s = substr($dtstr, -2);
		$dtstr = substr($dtstr, 0, -2);
		$m = substr($dtstr, -2);
		$dtstr = substr($dtstr, 0, -2);
		$h = substr($dtstr, -2);
		$dtstr = substr($dtstr, 0, -2);
		$d = substr($dtstr, -2);
		$dtstr = substr($dtstr, 0, -2);
		$M = substr($dtstr, -2);
		$dtstr = substr($dtstr, 0, -2);
		$y = $dtstr;

		if ($M + 0 == 0 && $d + 0 == 0) return $y; // Year only
		elseif ($M + 0 > 0 && $d + 0 == 0) return $M."/".$y; // Year+Month
		elseif ($M + 0 == 0 && $d + 0 > 0) { // Year+Quart
			$arr = [null, "I", "II", "III", "IV"];
			return $arr[$d + 0]."/".$y;
		}
		return sprintf($format, $y, $M, $d, $h, $m, $s);
	}

	/**
	 * @param string $date Date
	 * @param string $format Date format ("YYYY-MM-DD h:mm:ss" for 2009-11-19 2:35:00 p.m.; "D.M.YYYY HH:mm" for 1.1.2017 14:35)
	 *
	 * @return mixed
	 */
	public static function formatDateTime2($date, $format)
	{
		if ($date === false) $date = time();
		$out = $format;

		$ddd = ["Ne", "Po", "Út", "St", "Čt", "Pá", "So", "Ne"];
		$dddd = ["Neděle", "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota", "Neděle"];
		$mmm = ["", "Led", "Úno", "Bře", "Dub", "Kvě", "Čer", "Čec", "Srp", "Zář", "Říj", "Lis", "Pro"];
		$mmmm = ["", "Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"];

		// Day
		$out = str_replace("DDDD", $dddd[date("w", $date)], $out);
		$out = str_replace("DDD", $ddd[date("w", $date)], $out);
		$out = str_replace("DD", date("d", $date), $out);
		$out = str_replace("D", date("j", $date), $out);

		// Month
		$out = str_replace("MMMM", $mmmm[date("n", $date)], $out);
		$out = str_replace("MMM", $mmm[date("n", $date)], $out);
		$out = str_replace("MM", date("m", $date), $out);
		$out = str_replace("M", date("n", $date), $out);

		// Year
		$out = str_replace("YYYY", date("Y", $date), $out);
		$out = str_replace("YY", date("y", $date), $out);

		// Hour
		$out = str_replace("HH", date("H", $date), $out);
		$out = str_replace("H", date("G", $date), $out);
		$out = str_replace("hh", date("h", $date), $out);
		$out = str_replace("h", date("g", $date), $out);

		// Minute and second
		$out = str_replace("mm", date("i", $date), $out);
		$out = str_replace("ss", date("s", $date), $out);

		return $out;
	}

	public static function subvalSort($a, $subkey)
	{
		if ($a == array()) return false;
		foreach ($a as $k => $v) $b[$k] = strToLower($v[$subkey]);
		asort($b);
		$c = [];
		foreach ($b as $key => $val) $c[] = $a[$key];
		return $c;
	}

	/** Gets value from array by path (null if not found), or sets value in (compatible) array by path
	 *
	 * @param array $array Array
	 * @param string $path Path (key1_0_key2_1_key3, where numbers are indexes in arrays)
	 * @param mixed $value If defined, set value and return changed array. Return value otherwise.
	 *
	 * @return null
	 */
	public static function arrayPath(array $array, string $path, $value = null)
	{
		$p = explode("_", $path);
		$temp = &$array;
		foreach ($p as $key) $temp = &$temp[$key];
		if ($value === null) return $temp;
		$temp = $value;
		return $array;
	}


	public static function urlEncode($str)
	{
		return str_replace(["%21"], ["!"], rawUrlEncode($str));
	}

	public static function urlDecode($str)
	{
		return rawUrlDecode($str);
	}

	public static function base64Encode($str)
	{
		return strtr(base64_encode($str), "+/=", "-_,");
	}

	public static function base64Decode($str)
	{
		return base64_decode(strtr($str, "-_,", "+/="));
	}

	/**
	 * Generates Universal Unique ID ("UUID" or "GUID")
	 *
	 * @param bool $compressed
	 *
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
	 * Send text (=non-HTML) UTF8 mail. TODO: Mail should be DataStore
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
			$headers = self::cs_utf2ascii($headers);
			$subject = self::cs_utf2ascii($subject);
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
	public static function convert($data, $arg1 = "", $arg2 = "", $arg3 = "", $args = [])
	{
		$fromFormat = "";
		$toFormat = QPage::getInstance()->outputFormat();
		$toType = "";
		if ($data === null) throw new \Exception("Error: null is not convertible."); // TODO: Maybe debug only? May return empty string.

		// Data as Object
		if (is_object($data)) {
			$fromFormat = Util::getClassName($data);
			if ($arg2 === "") { // convert(obj, p1)
				$toType = $arg1;
			} elseif ($arg3 === "") { // convert(obj, p1, p2)
				$toFormat = $arg1;
				$toType = $arg2;
			} else {
				throw new \Exception("Error: Convert for ".$fromFormat." cannot define fromFormat, becuase it's derived from its type.");
			}

			// Data as String
		} elseif (is_string($data)) {
			$data = trim($data);

			if ($arg2 === "") { // convert("str", p1)
				$fromFormat = $arg1;
			} elseif ($arg3 === "") { // convert("str", p1, p2)
				$fromFormat = $arg1;
				$toFormat = $arg2;
			} else { // convert("str", p1, p2, p3)
				$fromFormat = $arg1;
				$toFormat = $arg2;
				$toType = $arg3;
			}

			// Data as Array
		} elseif (is_array($data)) {
			$fromFormat = count($data) == 0 || (isset($data[0]) && isset($data[count($data) - 1])) ? "array" : "hashmap";
			$toFormat = $arg1;
		}

		$convClass = QPage::getInstance()->loadConverter($fromFormat, $toFormat, $toType);
		return $convClass->convert($data, new Dict($args));

		/*$convPath = "converters/".strToLower($fromFormat."_".$toFormat.($toType != "" ? "_".$toType : "")).".php";
		$path = file_exists(QPage::getInstance()->pathApp().$convPath) ? QPage::getInstance()->pathApp() : QPage::getInstance()->pathAppCommon();
		if (!file_exists($path.$convPath)) throw new \Exception("Error: Converter ".substr($convPath, 11, -4)." not found!"); // TODO: Disable for PROD for performance
		include_once $path.$convPath;
		$convClass = "com\\qetrix\\apps\\".QPage::getInstance()->name()."\\converters\\".$fromFormat."_".$toFormat.($toType != "" ? "_".$toType : "");
		if (!class_exists($convClass)) $convClass = "com\\qetrix\\apps\\common\\converters\\".$fromFormat."_".$toFormat.($toType != "" ? "_".$toType : "");
		return (new $convClass())->convert($data, $args);*/
	}

	public static function vincentyDistance($lat1, $lon1, $lat2, $lon2)
	{
		$a = 6378137;
		$b = 6356752.314245;
		$f = ($a - $b) / $a; //1 / 298.257223563; // WGS-84 ellipsoid params; ($a - $b) / $a; //flattening of the ellipsoid
		$L = deg2rad($lon2) - deg2rad($lon1); //difference in longitude
		$U1 = atan((1 - $f) * tan(deg2rad($lat1))); //U is 'reduced latitude'
		$U2 = atan((1 - $f) * tan(deg2rad($lat2)));
		$sinU1 = sin($U1);
		$sinU2 = sin($U2);
		$cosU1 = cos($U1);
		$cosU2 = cos($U2);
		$lambda = $L;
		$lambdaP = 2 * pi();
		$i = 20;
		while (abs($lambda - $lambdaP) > 1e-12 and --$i > 0) {
			$sinLambda = sin($lambda);
			$cosLambda = cos($lambda);
			$sinSigma = sqrt(($cosU2 * $sinLambda) * ($cosU2 * $sinLambda) + ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda) * ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda));
			if ($sinSigma == 0) return 0; //co-incident points
			$cosSigma = $sinU1 * $sinU2 + $cosU1 * $cosU2 * $cosLambda;
			$sigma = atan2($sinSigma, $cosSigma);
			$sinAlpha = $cosU1 * $cosU2 * $sinLambda / $sinSigma;
			$cosSqAlpha = 1 - $sinAlpha * $sinAlpha;
			$cos2SigmaM = $cosSigma - 2 * $sinU1 * $sinU2 / $cosSqAlpha;
			if (is_nan($cos2SigmaM)) $cos2SigmaM = 0; //equatorial line: cosSqAlpha=0 (6)
			$c = $f / 16 * $cosSqAlpha * (4 + $f * (4 - 3 * $cosSqAlpha));
			$lambdaP = $lambda;
			$lambda = $L + (1 - $c) * $f * $sinAlpha * ($sigma + $c * $sinSigma * ($cos2SigmaM + $c * $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM)));
		}
		if ($i == 0) return false; //formula failed to converge
		$uSq = $cosSqAlpha * ($a * $a - $b * $b) / ($b * $b);
		$A = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
		$B = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));
		$deltaSigma = $B * $sinSigma * ($cos2SigmaM + $B / 4 * ($cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM) - $B / 6 * $cos2SigmaM * (-3 + 4 * $sinSigma * $sinSigma) * (-3 + 4 * $cos2SigmaM * $cos2SigmaM)));
		$d = $b * $A * ($sigma - $deltaSigma);
		return number_format($d, 3, '.', ''); //round to 1mm precision
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

/** Dict is a wrapper for an array
 * @link https://wiki.qetrix.com/Dict
 */
final class Dict
{
	private $_data = [];

	public function __construct($value = [], $add = [])
	{
		if (is_object($value)) $value = (get_class($value) == "com\\qetrix\\libs\\Dict" ? $value->_data : []);
		$this->_data = array_merge(array_change_key_case($value, CASE_LOWER), array_change_key_case($add, CASE_LOWER));
	}

	public function get(string $key, $valueIfNotFound = "")
	{
		$key = strToLower($key."");
		return isset($this->_data[$key]) ? $this->_data[$key] : $valueIfNotFound;
	}

	public function getInt(string $key, int $valueIfNotFound = 0): int
	{
		return (int)$this->get($key, $valueIfNotFound);
	}

	public function set(string $key, $value = null): Dict
	{
		if (is_array($key) && $value === null) $this->_data = array_merge($this->_data, array_change_key_case($key, CASE_LOWER));
		elseif (is_array($value)) $this->_data = array_merge($this->_data, array_change_key_case($value, CASE_LOWER));
		else $this->_data[strToLower($key."")] = $value;
		return $this;
	}

	/**
	 * @param string $key
	 * @param bool $allowEmpty
	 *
	 * @return bool
	 */
	public function has(string $key, $allowEmpty = false): bool
	{
		return isset($this->_data[strToLower($key."")]) && ($allowEmpty || $this->_data[strToLower($key."")] != "");
	}

	public function del(string $key): Dict
	{
		unset($this->_data[strToLower($key."")]);
		return $this;
	}

	public function toArray(): array
	{
		return $this->_data;
	}
}

/** @link https://wiki.qetrix.com/QEnum */
abstract class QEnum
{
	private static $consts = [];

	private static function getConstants()
	{
		$cls = get_called_class();
		if (!array_key_exists($cls, self::$consts)) self::$consts[$cls] = (new \ReflectionClass($cls))->getConstants();
		return self::$consts[$cls];
	}

	public static function toArray()
	{
		return array_flip(self::getConstants());
		// return array_map(function ($value) { return rtrim($value, "_"); }, $arr);
	}

	public static function toString($value)
	{
		$constants = self::toArray();
		return isset($constants[$value]) ? $constants[$value] : null;
	}

	/** Returns value for provided key, useful e.g. when loading string values from config
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public static function getValue($name)
	{
		return self::getConstants()[$name];
	}
}
