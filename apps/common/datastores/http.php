<?php
namespace com\qetrix\apps\common\datastores;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.02.16 | HTTP and REST DataStore. GET, POST with file upload, COOKIES, HTTP headers etc.
 */

// TODO: Not only read data, but provide data (interface) as well! Like SOAP - not only client, but server as well

use com\qetrix\libs\DataStore;
use com\qetrix\libs\QApp;
use com\qetrix\libs\Util;

class Http extends DataStore
{
	// host: http://www.example.com:port/
	// db: app_name/
	// prefix: q01/
	// username: http auth
	// password: http auth

	// TODO HOWTO: method (post, get, put, delete) + set cookies?
	// TODO HOWTO: proxy?

	/* http://www.zdrojak.cz/clanky/rest-architektura-pro-webove-api/
	 * TEST: http://wwwinfo.mfcr.cz/cgi-bin/ares/darv_bas.cgi?ico=71337938&ver=1.0.2
	 * create, read, update, delete
	 * POST (Create)
	 * GET (Retrieve/Read)
	 * PUT (Update)
	 * DELETE (Delete)
	*/

	function __construct(QApp $app)
	{
		$this->Http($app);
	}

	function Http(QApp $app)
	{
		parent::DataStore($app);
		$this->data["get"] = array_change_key_case($_GET, CASE_LOWER);
		$this->data["post"] = array_change_key_case($_POST, CASE_LOWER);
		$this->data["cookie"] = array_change_key_case($_COOKIE, CASE_LOWER);
		$this->data["file"] = array_change_key_case($_FILES, CASE_LOWER);
		$this->data["env"] = array_change_key_case($_SERVER, CASE_LOWER);
	}

	function appCreate($appName)
	{
	}

	function appList()
	{
	}

	function appDelete($appName)
	{
	}

	function particleList()
	{
	}

	function particleSet($pid, $pv = false, $pr = false, $po = false, $pp = false)
	{
	}

	function particleDel($pid)
	{
	}

	function setData($name, $text)
	{
	}
	function getData()
	{
		return $this->data;
	}

	function activate($db)
	{
		$this->data["env"]["pathroot"] = str_replace("qetrix.php", "", $this->data["env"]["php_self"]);
		$this->data["env"]["path"] = $this->data["env"]["pathroot"].($this->app()->isMultiApp() ? $this->app()->name()."/" : "");
		$this->data["env"]["pathapp"] = $this->data["env"]["pathroot"].($this->app()->isMultiApp() ? "apps/".$this->app()->name()."/" : "");
		$this->data["env"]["pathappcommon"] = $this->data["env"]["pathroot"].($this->app()->isMultiApp() ? "apps/common/" : "");
		$this->data["env"]["pathcontent"] = $this->data["env"]["pathapp"]."content/".($this->app()->hasUserSpaces() ? $this->app()->username()."/" : "");
		$this->data["env"]["pathres"] = $this->data["env"]["pathapp"]."res/";
		$this->data["env"]["pathrescommon"] = $this->data["env"]["pathappcommon"]."res/";
		$this->data["env"]["pathfull"] = "//".$this->data["env"]["server_name"].$this->data["env"]["path"];
		$this->data["env"]["pathrel"] = substr($this->data["get"]["_p"], strlen($this->app()->name()));

		if ($this->data["env"]["remote_addr"] == "127.0.0.1" || $this->data["env"]["remote_addr"] == "::1") $this->data["env"]["localhost"] = "1";
		$this->data["env"]["user_agent"] = $this->data["env"]["http_user_agent"];
	}

	function get($scope, $name = null)
	{
		if (!isset($this->data[$scope])) throw new \Exception("Err#xy: Invalid scope in HTTP DS: ".$scope);
		if ($name === null) return $this->data[$scope];
		$name = strToLower($name);

		if (!isset($this->data[$scope][$name])) return false;
		return $this->data[$scope][$name];

		/*switch ($scope) {
			case "file":
				if ($name === null) return count($_FILES) > 0;
				if ($_FILES[$name]["error"] == UPLOAD_ERR_OK && is_uploaded_file($_FILES[$name]["tmp_name"])) { //checks that file is uploaded
					$_FILES[$name]["contents"] = file_get_contents($_FILES[$name]["tmp_name"]);
				}
		*/
	}

	function set($scope, $name, $value)
	{
		switch ($scope) {
			case "cookie":
				return $this->cookie($name, $value);
			//return $this;
			case "header":
				// Some headers may just like a command, without value
				if (!headers_sent($filename, $linenum)) header($name.($value != "" ? ": ".$value : ""));
				else throw new \Exception("Unable to set header \"".$name.($value != "" ? "\" to \"".$value : "")."\", headers already sent in ".$filename." on line ".$linenum.".");
				return $this;
			case "env":
				break;
			case "get":
			case "post":
			case "file":
				throw new \Exception("Http DS: Unable to set ".$name." into scope ".$scope.", scopes get, post, file are read only!");
			default:
				throw new \Exception("Invalid scope: ".$scope."");
		}
		return null;
	}

	public function output($str)
	{
		echo $str;
	}


	private function request()
	{
		$url = $this->host;
		$access = $this->password;
		$proxy = ""; //'127.0.0.1:8888';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow HTTP 3xx redirects
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Data will be returned to $result
		curl_setopt($ch, CURLOPT_HEADER, 1); // Return response headers
		//curl_setopt($ch, CURLOPT_USERAGENT, "QetriX");

		// POST data
		//foreach($fields as $key=>$value) $fields_string .= $key.'='.urlencode($value).'&';
		//rtrim($fields_string, '&');
		//curl_setopt($ch, CURLOPT_POST, count($fields));
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

		if ($proxy != "") {
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
			//curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyauth);
		}

		if ($access !== null) {
			curl_setopt($ch, CURLOPT_USERPWD, $access);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}
		//$info = curl_getinfo($ch);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	/**
	 * Combined method for get/set cookies, will work outside DS.
	 * Note: You can set cookie value ONLY BEFORE you send anything to output!
	 *
	 * @param string $name Name of the cookie (required)
	 * @param null|string $value Value to be set for the cookie, empty string deletes the cookie. If $value is omitted (or null), a value of the cookie is returned
	 * @param int $expire Expiration expire, default=0
	 * @param string $path Domain
	 * @param string $domain
	 *
	 * @return mixed: true=success, talse=error, null=deleted, string=cookie value (when in:$value is null)
	 */
	private function cookie($name, $value = null, $expire = 0, $path = null, $domain = null)
	{
		if ($value === null) return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
		if ($value == "") {
			$expire = time() - 3600;
			$value = null;
		}
		if ($path === null) $path = $this->data["env"]["path"];
		if ($domain === null) $domain = ($_SERVER["SERVER_NAME"] != "localhost" ? str_replace("www.", "", $_SERVER["SERVER_NAME"]) : "");
		$res = setcookie($name, $value, $expire, $path, $domain);
		if ($res) {
			if ($expire == time() - 3600 && $value === "") {
				unset($_COOKIE[$name]); /// Del cookie
				return null;
			}
			return true;
		} else return false;
	}
}
