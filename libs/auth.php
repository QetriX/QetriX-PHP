<?php declare(strict_types = 1);
namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.04.18 | Authentication for users and apps
 */

use com\qetrix\apps\common\datastores\MySQL;

// TODO: It's too dependent with HTTP DS. Token can be pased as get(_t) and returned as header(token)

class Auth
{
	private $_token = null;
	private $_isAuth = false;
	private $userInfo = [];
	private $groups = [];

	private $loginFormUsername = "loginform-username";
	private $loginFormPassword = "loginform-password";
	private $loginFormGoTo = "loginform-goto";

	/** @var MySQL */
	private $ds;
	/** @var QPage */
	private $_page;

	function __construct(DataStore $ds = null)
	{
		return $this->Auth($ds);
	}

	// TODO: Add REMOTE_USER support
	function Auth(DataStore $ds = null)
	{
		if ($this->isAuth()) return $this;
		$this->_page = QPage::getInstance();
		if ($this->page()->get("remote_user") != "") {
		} elseif ($this->page()->get("auth_user") != "") {

		} else {
			$this->ds = $ds === null ? $this->page()->ds() : $ds;
		}
		$oldToken = $this->page()->get($this->tokenName());
		if ($oldToken != "") {
			$newToken = $this->token();
			if ($this->ds->authToken($oldToken, $newToken, Util::crc32($this->page()->get("user_agent")))) $this->token($newToken); else $this->logout();
		}
		return $this;
	}

	function page()
	{
		return $this->_page;
	}

	function setFormFields($username, $password, $goto = null)
	{
		$this->loginFormUsername = $username;
		$this->loginFormPassword = $password;
		if ($goto !== null) $this->loginFormGoTo = $goto;
	}

	function signup($username, $password, $password2, $email)
	{
		if ($password != $password2) return -1;
		$id = $this->ds->authSignup($username, $this->password($password), $email);
		return $id;
	}

	function login($data) //$username = null, $password = null)
	{
		if (!isset($data[$this->loginFormUsername]) || !isset($data[$this->loginFormPassword])) return false;
		if ($this->isAuth()) return true;
		$ua = $this->page()->get("user_agent");
		$ip = $this->page()->get("remote_addr");
		$newToken = $this->token();
		$userdata = $this->ds->authLogin($data[$this->loginFormUsername]);
		//Util::log($userdata, "auth.login.userdata");
		if ($userdata !== null) {
			if (password_verify($data[$this->loginFormPassword], $userdata["password"])) {
				$this->ds->authLoginSet($userdata["id"], $newToken, Util::crc32($ua), $ip, $this->getOS($ua).$this->getBrowser($ua));
				$this->token($newToken);
				if (isset($data[$this->loginFormGoTo]) && strpos($data[$this->loginFormGoTo], "//") === false) {
					$this->page()->set("location", $this->loginFormGoTo);
					exit();
				}
			} else return false;
		}
		return $this->_isAuth;
	}

	function get($key)
	{
		if ($this->ds === null) return "";
		if ($this->userInfo == []) {
			//$info = $this->ds->authDetail($this->_token);
			//$this->userInfo = count($info) > 0 ? $info[0] : null;
			$this->userInfo = $this->ds->authDetail($this->_token); // *MNo 20160502
		}
		if (!isset($this->userInfo[$key])) return "";
		return $this->userInfo[$key];
	}

	function getGroups()
	{
		if ($this->groups == []) $this->groups = $this->ds->authGroups($this->get("id"));
		return $this->groups;
	}

	function memberOf($group)
	{
		if ($this->groups == []) $this->getGroups();
		return (isset($this->groups[$group]));
	}

	function checkAccess($module, $method, $pid = null)
	{
		if ($this->groups == []) $this->getGroups();
		$this->ds->authAccess($module, $method, $this->groups, $pid);
	}

	function logout()
	{
		$this->_isAuth = false;
		$this->_token = null;
		$this->setToken("");
		$ua = $this->page()->get("user_agent");
		$this->ds->authClose($this->_token, Util::crc32($ua));
	}

	private function token($value = false)
	{
		if ($value === false) return Util::uuid(true);
		$this->_isAuth = true;
		$this->_token = $value;
		$this->setToken($value);
		return $this;
	}

	private function setToken($value)
	{
		$this->page()->set($this->tokenName(), $value);
		//$this->app()->envDS()->set("header", $this->tokenName, $value); // TODO
	}

	private function tokenName()
	{
		return $this->page()->authTokenName();
	}

	/** Generate new password */
	function password($password)
	{
		return password_hash($password, PASSWORD_BCRYPT);
	}

	public function isAuth()
	{
		return $this->_isAuth;
	}

	public function username($value = false)
	{
		if ($value === false) return $this->get("un");
		$this->_isAuth = false;
		$this->userInfo = ["username" => $value]; // You can force user name, but the current user will be unauthenticated.
		return $this;
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

final class UserState
{
	const blocked = -1;
	const created = 0;
	const valid = 1;
	const renewpw = 2;
	const lostpw = 3;
	const admin = 8;
	const group = 9;
}
