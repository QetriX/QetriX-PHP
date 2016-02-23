<?php
namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.02.04 | Authentication for users and apps
 */

class Auth
{
	private $_token = null;
	private $_isAuth = false;
	private $userID = null;
	private $userInfo = null;

	private $ds;
	/** @var QApp */
	private $app;

	function __construct(DataStore $ds = null)
	{
		return $this->Auth($ds);
	}

	function Auth(DataStore $ds = null)
	{
		if ($this->isAuth()) return $this;
		$this->app = QApp::getInstance();
		$this->ds = $ds === null ? $this->app->ds() : $ds;
		$oldToken = $this->app->envDS()->get("cookie", "token");
		if ($oldToken != "") {
			$newToken = $this->token();
			$ua = $this->app->envDS()->get("env", "user_agent");
			if ($this->ds->authToken($oldToken, $newToken, Util::crc32($ua))) $this->token($newToken); else $this->logout();
		}
		return $this;
	}

	function signup($username, $password, $password2, $email)
	{
		if ($password != $password2) return -1;
		$id = $this->ds->authSignup($username, $password, $email);
		return $id;
	}

	function login($username = null, $password = null)
	{
		if ($username == "" || $password == "") return false;
		if ($this->isAuth()) return true;

		$ua = $this->app->envDS()->get("env", "user_agent");
		$ip = $this->app->envDS()->get("env", "remote_addr");
		$newToken = $this->token();
		$userdata = $this->ds->authLogin($username);
		if ($userdata !== null) {
			if (password_verify($password, $userdata["password"])) {
				$this->ds->authLoginSet($userdata["id"], $newToken, Util::crc32($ua), $ip, $this->getOS($ua).$this->getBrowser($ua));
				$this->token($newToken);
			} else return false;
		}
		return $this->_isAuth;
	}

	function get($key)
	{
		if ($this->userInfo == null) $this->userInfo = $this->ds->authDetail($this->_token)[0];
		if (!isset($this->userInfo[$key])) return "";
		return $this->userInfo[$key];
	}

	function checkAccess($module, $method)
	{
		$this->ds->authAccess($module, $method, $this->userID);
	}

	function logout()
	{
		$this->_isAuth = false;
		$this->_token = null;
		$this->app->envDS()->set("cookie", "token", "");
		$ua = $this->app->envDS()->get("env", "user_agent");
		$this->ds->authClose($this->_token, Util::crc32($ua));
	}

	private function token($value = false)
	{
		if ($value === false) return Util::uuid(true);
		$this->app->envDS()->set("cookie", "token", $value);
		$this->_isAuth = true;
		$this->_token = $value;
		return $this;
	}

	// Generate new password
	function password($password)
	{
		return password_hash($password, PASSWORD_BCRYPT);
	}
	// Not only human, but app auth as well

	public function isAuth()
	{
		return $this->_isAuth;
	}

	public function username()
	{
		return $this->get("un");
	}

	public function email()
	{
		return $this->get("email");
	}

	public function name()
	{
		return $this->get("name") === null ? $this->username() : $this->get("name");
	}

	public function lang()
	{
		return "en";
	}

	public function id()
	{
		return $this->get("id");
	}

	public function timezone()
	{
		return 60; // TODO!!!! Return difference from UTC in minutes
	}

	function getOS($userAgent)
	{
		$os = array(
			"/ws nt 10/i" => "10", "/ws nt 6.3/i" => "W9", "/ws nt 6.2/i" => "W8", "/ws nt 6.1/i" => "W7", "/ws nt 6.0/i" => "WV", "/ws nt 5.2/i" => "23",
			"/ws nt 5.1/i" => "XP", "/ws xp/i" => "XP", "/ws nt 5.0/i" => "2K", "/ws me/i" => "ME", "/win98/i" => "98", "/win95/i" => "95", "/win16/i" => "31",
			"/macintosh|mac os x/i" => "MX", "/mac_powerpc/i" => "M9", "/linux/i" => "LI", "/ubuntu/i" => "UB",
			"/iphone/i" => "IH", "/ipod/i" => "IO", "/ipad/i" => "IA", "/android/i" => "AN", "/blackberry/i" => "BB", "/windows phone/i" => "WP", "/windows mobile/i" => "WM", "/windows ce/i" => "CE", "/webos/i" => "MB"
		);
		foreach ($os as $regex => $value) if (preg_match($regex, $userAgent)) return $value;
		return "XX";
	}

	function getBrowser($userAgent)
	{
		$br = array(
			"/msie 11./i" => "11", "/msie 10./i" => "10", "/msie 9./i" => "I9", "/msie 8./i" => "I8", "/msie/i" => "IE", "/edge/i" => "EG", "/iemobile/i" => "IM",
			"/firefox/i" => "FF", "/safari/i" => "SF", "/chrome/i" => "CH", "/opera/i" => "OP", "/netscape/i" => "NS", "/maxthon/i" => "MX", "/vivaldi/i" => "VV", "/konqueror/i" => "KQ", "/mobile/i" => "MB"
		);
		foreach ($br as $regex => $value) if (preg_match($regex, $userAgent)) return $value;
		return "XX";
	}
}
