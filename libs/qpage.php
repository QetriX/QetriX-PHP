<?php declare(strict_types = 1);
namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 17.01.04 | QetriX Page Class, platform dependent.
 */

use com\qetrix\libs\components\QList;
use com\qetrix\libs\components\QView;

//use com\qetrix\libs\Util;

include __DIR__.DIRECTORY_SEPARATOR."util.php";

/** @link http://wiki.qetrix.com/QPage */
class QPage
{
	/** @var QPage null */
	private static $_instance; // Static reference to current QetriX Page

	private $_appName = ""; // App Name. Recommended 1-16 chars, [a-z][0-9] only. Used in paths, keys, possibly ds conn etc.
	private $_siteName = ""; // Site Name [optional]. Recommended 1-16 chars, [a-z][0-9] only. Added into path, after AppName.
	private $_text = ""; // Language specific, appropriate to $_name
	private $_auth = null;
	private $_authTokenName = "_t";
	//private $_ds = null; // Default DS, where Delm is stored
	private $_dsList = []; // HashMap<String, DataStore>
	private $_stage = "";

	private $_outputFormat = "html"; // In what format should the output be rendered (what set of converters it uses)
	private $_lang = "en"; // Current language, English as a default language, incl. internationalization and localization (g11n/i18n) settings: date format, time format, units in general, temperature...
	private $_langs = ["en"]; // List of available languages
	private $_lbl = []; // HashMap<string, string>; Array of labels in current language
	private $_messages = []; // List<QMessage>
	private $_args; // HashMap<String, String>
	private $_data; // HashMap<String, String>
	private $formData = null;

	private $_path; // Page path, e.g. abc/def/
	private $_pathX; // Base path
	private $_pathBase; // Base path for links, e.g. /qetrix/myapp/
	private $_pathRes; // Resource path, e.g. /qetrix/myapp/res/ (CDN ready)
	private $_pathResCommon; // Common resource path, e.g. /qetrix/common/res/ (CDN ready)
	private $_pathContent; // Content path, e.g.  /qetrix/myapp/content/ (CDN ready) TODO: DataStore for uploading to CDN

	private $_pathRoot; // Script root path, e.g. /var/www/qetrix/
	private $_pathApp; // App root path, e.g. /var/www/qetrix/apps/myapp/
	private $_pathAppCommon; // App root path, e.g. /var/www/qetrix/apps/common/
	private $_pathAppContent; // App content path, e.g. /var/www/qetrix/apps/myapp/content/
	private $_pathAppData; // App data path, e.g. /var/www/qetrix/apps/myapp/data/
	private $_pathAppVars; // App vars path, e.g. /var/www/qetrix/vars/myapp/

	private $_isMultiApp = 0; // Uses this QetriX multi-page mode? (looks for /apps/ subdir in QetriX root dir)
	private $_defaultModule = ""; // What module should be used as default, when requested module wasn't found
	private $_allowedCookies = ["_c", "_f", "_l"];
	private $_features = [];

	private $_uid;
	private $_beginTime;
	private $_beginMem;

	/** PHP constructor */
	function __construct()
	{
		$this->QPage();
	}

	/** Class constructor */
	function QPage()
	{
		// To use $this in closures
		$self = $this;
		register_shutdown_function(function () use ($self) {
			$self->end();
		});

		spl_autoload_register(function ($name) use ($self) {
			// TODO: log what includes it uses to bundle them together
			$ifn = "";
			if ($self->isMultiApp()) {
				if (strlen($name) > 11) $ifn = $this->pathRoot().str_replace("\\", DIRECTORY_SEPARATOR, strToLower(substr($name, 11))).".php";
			} else {
				$shortname = str_replace("\\", DIRECTORY_SEPARATOR, strToLower(substr($name, 11)));
				if (substr($shortname, 0, 5) == "apps".DIRECTORY_SEPARATOR) $ifn = $this->pathRoot().substr($shortname, strpos($shortname, "/", 6) + 1).".php";
				else $ifn = $this->pathRoot().str_replace("\\", DIRECTORY_SEPARATOR, strToLower(substr($name, 11))).".php";
			}
			include $ifn;
		});

		$this->begin();

		// Define custom error handler
		set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($self) {
			$self->error($self, "Error #".$errno, $errstr." (".$errfile.":".$errline.")", debug_backtrace(0));
		});

		// Define custom exception handler
		set_exception_handler(function ($ex) use ($self) {
			/** @var $ex \Exception */
			$self->error($self, "Exception", $ex->getMessage()." in ".$ex->getFile()." on line ".$ex->getLine(), $ex->getTrace());
		});
	}

	/** Returns instance of QPage */
	static function getInstance()
	{
		if (is_null(self::$_instance)) self::$_instance = new self;
		return self::$_instance;
	}

	private function begin()
	{
		// Get the start time and memory, e.g. for performance monitoring
		$this->_beginTime = microtime(true);
		$this->_beginMem = memory_get_usage();
		$this->_uid = Util::getRandomString(6);
		$this->addMessage("log", "begin", "begin\t".$_SERVER["REMOTE_ADDR"].(isset($_GET["_p"]) ? "\t".$_GET["_p"] : ""));

		// Set all encodings to UTF-8
		mb_internal_encoding("UTF-8");
		mb_language("uni");
		mb_regex_encoding("UTF-8");
		mb_http_output("UTF-8");
		mb_http_input("UTF-8");

		// Set response HTTP headers
		$this->set("Expires", "Thu, 19 Nov 2009 14:35:26 GMT");
		$this->set("Cache-Control", "no-cache, no-store, max-age=0, must-revalidate");
		$this->set("Pragma", "no-cache");

		// QetriX uses UTC TZ
		date_default_timezone_set("UTC");

		// Load data for the Page
		$this->_args = array_merge(array_intersect_key(array_change_key_case($_COOKIE, CASE_LOWER), array_flip(["_c", "_f", "_l", $this->authTokenName()])), array_change_key_case($_GET, CASE_LOWER));
		unset($this->_args["remote_user"], $this->_args["auth_user"]); // Disallow these from GET and cookies
		$this->_data = array_merge($this->_args, array_change_key_case($_SERVER, CASE_LOWER));
		unset($this->_args["_p"]);

		if ($this->get("remote_addr") == "127.0.0.1" || $this->get("remote_addr") == "::1") $this->_data["localhost"] = "1";
		$this->_data["user_agent"] = $this->get("http_user_agent");

		$this->_data["request_protocol"] = (($this->get("https") != "" && strToLower($this->get("https")) != "off")
			|| strToLower($this->get("http_x_forwarded_proto")) == "https"
			|| strToLower($this->get("http_x_forwarded_ssl")) == "on"
		) ? "https" : "http";

		$base = $this->_data["php_self"];
		$base = substr($base, 0, strpos($base, ".php") + 4);
		$base = substr($base, 0, strrpos($base, "/"));
		$this->_pathBase = $base."/";
		$this->_pathX = $this->_pathBase;
		$this->_pathResCommon = $this->_pathX.($this->isMultiApp() ? "apps/common/" : "")."res/";
		$this->_pathRoot = substr(__DIR__, 0, -4);
		$this->_pathAppCommon = $this->pathRoot().($this->isMultiApp() ? "apps".DIRECTORY_SEPARATOR."common".DIRECTORY_SEPARATOR : "");
		/// USE "http_host" and "remote_port" for multi-apps on prod-server
		$this->_outputFormat = $this->suggestOutputFormat();
		$this->_lang = $this->suggestLanguage();
		$this->_stage = $this->has("localhost") ? QModuleStage::dev : QModuleStage::prod;
	}

	private function error(QPage $self, $heading, $message, $trace)
	{
		if ($self->_appName == "") $self->_appName = "common";
		$self->set("status", "HTTP/1.1 500 Internal Server Error");
		if ($this->has("localhost")) {
			$exv = new QView("ex", $heading);
			$exv->features(["common.css"]);
			$exv->add(explode("\n", $message));

			$stc = new QList("stc", "Stack Trace");
			foreach ($trace as $exl) {
				if (!isset($exl["file"])) continue;
				$stc->add(["file" => $exl["file"], "line" => $exl["line"], "function" => $exl["function"]]);
			}
			$exv->add(Util::convert($stc, "table"));
			$exv->add(QPage::debug());
			// file_put_contents("php://stderr", ...) // TODO!!!
			$self->set("output", Util::convert($exv, "page"));
		}
		exit(1);
	}

	private function debug()
	{
		$self = QPage::getInstance();
		$exv = new QView("debug");

		if ($self->hasFormData()) {
			$scp = new QList("data", "Form data");
			foreach ($self->formData as $k => $v) {
				$scp->add(["name" => $k, "text" => is_array($v) ? "[array]" : $v]);
			}
			$exv->add(Util::convert($scp, "table"));
		}

		$scp = new QList("data", "Page data");
		foreach ($self->_data as $k => $v) $scp->add(["name" => $k, "text" => $v]);
		$exv->add(Util::convert($scp, "table"));

		return Util::convert($exv);
	}

	private function end()
	{
		// TODO: Messaging!!!
		// file_put_contents($this->pathAppVars()."log/".date("Ymd").".log", date("YmdHis")."\tend\ttime:".round(microtime(true) - $this->_beginTime, 2).", mem:".ceil((memory_get_usage() - $this->_beginMem) / 1024)."/".ceil(memory_get_peak_usage() / 1024)."\n\n", FILE_APPEND);
	}

	/** @return string PHP Server QetriX root path */
	public function pathRoot()
	{
		return $this->_pathRoot;
	}

	/** @return string PHP Server page path */
	public function pathApp()
	{
		return $this->_pathApp;
	}

	/** @return string PHP Server common page path */
	public function pathAppCommon()
	{
		return $this->_pathAppCommon;
	}

	/** @return string PHP Server data path */
	public function pathAppData()
	{
		return $this->_pathAppData;
	}

	/** @return string PHP Server content path */
	public function pathAppContent()
	{
		return $this->_pathAppContent;
	}

	/** @return string PHP Server vars path - containing /backups, /cache, /log, /sessions subdirectoris */
	public function pathAppVars()
	{
		return $this->_pathAppVars;
	}

	/**
	 * @param null|string $value Set page path (redirect) or omit/null for getting page path
	 * @param bool $redir
	 *
	 * @return QPage|string HTML Client page path or QPage when setting
	 */
	public function path($value = null, $redir = false)
	{
		if ($value === null) return $this->_path;
		if ($redir) header("HTTP/1.1 301 Moved Permanently");
		elseif (count($_POST) > 0) header("HTTP/1.1 303 See Other"); // Correct status code for PRG (Post/Redirect/Get) pattern
		$this->set("path", $this->pathBase().$value);
		return $this;
	}

	/** @return string HTML Client page base path */
	public function pathBase()
	{
		return $this->_pathBase;
	}

	/** @return string HTML Client content path */
	public function pathContent()
	{
		return $this->_pathContent;
	}

	/** @return string HTML Client page resources path */
	public function pathRes()
	{
		return $this->_pathRes;
	}

	/** @return string HTML Client common resources path */
	public function pathResCommon()
	{
		return $this->_pathResCommon;
	}

	/** @return string QetriX Application name, defined in config */
	public function appName()
	{
		return $this->_appName;
	}

	/** @return string QPage's text */
	public function text()
	{
		return $this->_text;
	}

	/**
	 * @param string $value Set output format, omit or empty to get output format
	 *
	 * @return string|QPage Code of currently using format (html, json, ...) or QPage for setter
	 */
	public function outputFormat($value = "")
	{
		if ($value === "") return $this->_outputFormat;
		$this->_outputFormat = $value;
		return $this;
	}

	/** @return string Code of currently using lang (en, cs, ...) */
	public function lang()
	{
		return $this->_lang;
	}

	public function authTokenName()
	{
		return $this->_authTokenName;
	}

	public function stage()
	{
		return $this->_stage;
	}

	public function scope()
	{
		return $this->_siteName;
	}

	/**
	 * @param string|DataStore $name
	 * @param DataStore $set
	 *
	 * @return mixed
	 */
	public function ds($name = "ds", DataStore $set = null)
	{
		//if ($value === null) return $this->_ds;
		if (is_object($name)) {
			$set = $name;
			$name = "ds";
		}
		if ($set === null) return isset($this->_dsList[$name]) ? $this->_dsList[$name] : null;
		$this->_dsList[$name] = $set;
		return $this;
	}

	/** @return Auth */
	public function auth()
	{
		// TODO: Define custom DS (MySQL, AD...)
		//Util::log(debug_backtrace(0));die;
		if ($this->_auth === null && $this->ds() !== null) $this->_auth = new Auth($this->ds());
		return $this->_auth;
	}

	/** @return bool (true/false). 0 = undefined, 1 = no, 2 = yes */
	public function isMultiApp() /// Usage in HTTP DS and loadDataStore and loadModule and parsePath
	{
		if ($this->_isMultiApp == 0) $this->_isMultiApp = is_dir($this->_pathRoot."apps") ? 2 : 1;
		return $this->_isMultiApp == 2;
	}

	/** Read values from request
	 *
	 * @param string $key Lower case key for requested value
	 * @param string $defval Default value, if not found or empty
	 *
	 * @return string
	 */
	public function get($key, $defval = "")
	{
		if ($this->has($key)) return $this->_data[$key];
		$key = strToLower($key."");
		return $this->has($key) ? $this->_data[$key] : $defval;
	}

	public function has($key)
	{
		return isset($this->_data[$key]);
	}

	/** Write values to response
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return QPage
	 */
	public function set($key, $value)
	{
		// Set header or cookie
		switch ($key) {
			case "path":
			case "redirect": // Deprecated
			case "redir": // Deprecated
			case "location": // Deprecated
			case "goto": // Deprecated
				header("Location: ".$value);
				break;
			case "status": // HTTP status
				header($this->get("server_protocol")." ".$value);
				break;
			case "output":
				echo $value;
				break;
			case $this->authTokenName():
				$this->cookie($key, $value, time() + 3600);
			default:
				if (in_array($key, $this->_allowedCookies)) $this->cookie($key, $value, time());
				else header($key.": ".$value);
		}
		return $this;
	}

	private function loadFormData()
	{
		if (isset($this->formData)) return;
		//$this->formData = array_change_key_case($_POST, CASE_LOWER);
		$this->formData = $_POST;

		if (count($_FILES) > 0) {
			foreach ($_FILES as $files_key => $files_value) {
				//$files_key = mb_strToLower($files_key);
				if (is_array($files_value["name"])) { // Array of files
					if (!isset($this->formData[$files_key])) $this->formData[$files_key] = [];
					$file_count = count($files_value["name"]);
					$file_keys = array_keys($files_value);
					for ($i = 0; $i < $file_count; $i++) foreach ($file_keys as $key) {
						if (is_array($_FILES[$files_key][$key][$i])) foreach ($_FILES[$files_key][$key][$i] as $elemName => $elemValue) $this->formData[$files_key][$i][$elemName][$key] = $elemValue;
						else $this->formData[$files_key][$i][$key] = $_FILES[$files_key][$key][$i];
					}
				} else { // Single file
					$this->formData[$files_key] = $files_value;
				}
			}
		}
	}

	/** Load data submitted by QForm
	 *
	 * @param string $key
	 *
	 * @return array|string - Map<String, String[]>
	 */
	public function getFormData($key = "")
	{
		$this->loadFormData();
		if ($key != "") return (isset($this->formData[$key]) ? $this->formData[$key] : "");
		return $this->formData;
	}

	/** Check if there are submitted QForm data
	 *
	 * @param string $key
	 *
	 * @return bool Return if there are new form data (POST)
	 */
	public function hasFormData($key = "")
	{
		$this->loadFormData();
		return $key === "" ? count($this->formData) > 0 : isset($this->formData[$key]) && $this->formData[$key] != "";
	}

	/** Helper function for working with cookies
	 *
	 * @param $name
	 * @param $value
	 * @param int $expire
	 *
	 * @return bool
	 */
	private function cookie($name, $value, $expire = 0)
	{
		if ($value == "") $expire = 1;
		if (setcookie($name, $value, $expire, $this->pathBase(), $this->has("localhost") ? "" : str_replace("www.", "", $this->get("server_name")))) {
			if ($expire === 1 || $value === "") unset($_COOKIE[$name]); /// Del cookie
			return true;
		}
		return false;
	}

	public function parsePath($path = "")
	{
		if ($this->appName() != "") throw new \Exception("Err#0101: Page has already been loaded.");
		$page = [];

		if ($path == "") {
			if (strpos($this->get("query_string"), "_p=") !== false && strpos($this->get("request_uri"), "_p=") === false) { // Using rewriter
				$path = $this->get("_p");
				unset($this->_data["_p"]);
			} elseif (strpos($this->get("php_self"), $this->get("script_name")) === 0) { // Not using rewriter
				$path = substr($this->get("php_self"), strlen($this->get("script_name")));
				$this->_pathBase = $this->get("script_name")."/";
			} else throw new \Exception("Err#0102: Error in path."); // TODO: Log this error!!! Good to konw what caused that...
		}
		$aPath = explode("/", trim($path, "/"));

		// Set the App
		$cfgData = [];
		if ($this->isMultiApp()) {
			$page["page"] = array_shift($aPath);
			if ($page["page"] === null) {
				if ($this->has("localhost")) { // localhost + multiapp
					/** @var DataStore $fsds */
					$fsds = $this->loadDataStore("ds", "filesystem", "", "", "");
					die(Util::convert($fsds->listApps()));
				}
				throw new \Exception("Err#xy: Undefined App!");
			}
			$this->_appName = $page["page"];
			$this->_pathApp = $this->pathRoot()."apps".DIRECTORY_SEPARATOR.$this->appName().DIRECTORY_SEPARATOR;
			$cfgData = $this->loadConfig();
			$this->_pathRes = $this->_pathX."apps/".$this->appName()."/res/";
			$this->_pathContent = $this->_pathX."apps/".$this->appName()."/content/";
			$this->_pathBase .= $this->appName()."/";
		} else {
			$page["page"] = "";
			$this->_pathRes = $this->_pathX."res/";
			$this->_pathApp = $this->pathRoot();
			$cfgData = $this->loadConfig();
			$this->_pathContent = $this->_pathX."content/";
		}

		if (in_array("app.hasSites", $cfgData)) $this->_siteName = array_shift($aPath); // TODO: I don't like that in_array, it's case sensitive and stuff and wrong
		$path = implode("/", $aPath);

		$this->_path = rtrim($path, "/");
		$this->_pathAppContent = $this->pathApp()."content".DIRECTORY_SEPARATOR;
		$this->_pathAppData = $this->pathApp()."data".DIRECTORY_SEPARATOR;
		$this->_pathAppVars = $this->pathRoot()."vars".DIRECTORY_SEPARATOR.$this->appName().DIRECTORY_SEPARATOR;
		if (!file_exists($this->_pathApp)) throw new \Exception("App ".$this->appName()." not found! Misspelled?");

		// Load config
		$page["path"] = implode("/", $aPath); // without page and stuff

		// Mod
		if (isset($aPath[0]) && is_numeric($aPath[0])) {
			$page["id"] = array_shift($aPath);
		} elseif (isset($aPath[0])) {
			$page["mod"] = array_shift($aPath);
		}
		if (!isset($page["mod"]) || (isset($page["mod"]) && in_array($page["mod"], array(null, "module")))) $page["mod"] = $page["page"];

		if (count($aPath) > 0) {
			// Func
			if (is_numeric($aPath[0])) {
				if (isset($page["id"])) {
					$arg = array_shift($aPath);
					$this->_args[] = $arg;
					$this->_data[] = $arg;
				} else {
					$page["id"] = array_shift($aPath);
					$page["func"] = array_shift($aPath);
					$this->_args["id"] = $page["id"];
					$this->_data["id"] = $page["id"];
					$this->_args["func"] = $page["func"];
					$this->_data["func"] = $page["func"];
				}
			} else {
				$page["func"] = array_shift($aPath);
				if (count($aPath) > 0 && !isset($page["id"]) && is_numeric($aPath[0])) $page["id"] = array_shift($aPath);
			}
			if (isset($page["func"])) $page["func"] = str_replace("-", "_", $page["func"]);

			// Args
			if (count($aPath) > 0) {
				$aPath2 = [];
				foreach ($aPath as $ap) {
					$sp = strpos($ap, ":");
					if ($sp !== false) $aPath2[substr($ap, 0, $sp)] = substr($ap, $sp + 1); // param:value in URL => $args->get("param") == "value"
					else $aPath2[] = $ap;
				}
				$this->_args = array_merge($this->_args, $aPath2);
				$this->_data = array_merge($this->_data, $aPath2);
			}
		}

		if (isset($page["mod"]) && ($page["mod"] == "content" || $page["mod"] == "data")) include_once $this->pathRoot()."libs/file.php";

		// App Config
		if (count($cfgData) > 0) $this->config($page, $cfgData);
		elseif (file_exists($this->_pathApp."config.php")) require_once $this->_pathApp."config.php";

		if ($this->appName() == "") $this->_appName = "qetrix"; // If not defined, set page name to "qetrix"
		error_reporting($this->stage() == QModuleStage::prod && false ? 0 : -1); // TODO!
		return $page;
	}

	private function loadConfig()
	{
		$config = "";
		if (file_exists($this->_pathApp."config.que")) $config = $this->_pathApp."config.que";
		elseif (file_exists($this->_pathApp."config_".$this->get("remote_port").".que")) $config = "config_".$this->get("remote_port").".que";
		if ($config == "") return [];
		return explode("\n", str_replace("\r", "", file_get_contents($config))); // TODO FIXME: Usage of file_get_contents is discouraged
	}

	private function config($page, $cfgData)
	{
		//$cfgData = explode("\n", str_replace("\r", "", file_get_contents($this->_pathApp.$conf))); // TODO FIXME: Usage of file_get_contents is discouraged

		// Process config file lines
		for ($rowNum = 0; $rowNum < count($cfgData); $rowNum++) {
			if (trim($cfgData[$rowNum]) == "") continue;
			$row = Util::getQueRow($cfgData[$rowNum], "\t");
			$fname = null;
			$func = null;
			foreach ($row as $func => $fname) break; // Get first key/value (which is func in key and possibly it's name in value)
			if (substr($func, 0, 2) == "//") continue;

			switch (strToLower($func)) {
				case "app.name": // Example: page.name	qetrix
					$this->_appName = $fname;
					$page["page"] = $fname;
					break;
				case "app.title": // Example: page.title	QetriX
					$this->_text = $fname;
					break;
				case "app.hasscopes":
					$this->_hasSites = true;
					break;
				case "app.ds": // Example: page.ds:QetriX_MySQL	type:MySQL	host:localhost	user:qetrix	scope:dbname	password:1234:-)	features:p,t,getpath	env:qetrix.com
					if (isset($row["env"]) && $this->get("server_name") != $row["env"]) continue;
					$this->loadDataStore(isset($row["name"]) ? $row["name"] : "ds", $fname,
						$row["host"],
						isset($row["scope"]) ? $row["scope"] : $this->appName(),
						isset($row["prefix"]) ? $row["prefix"] : "",
						$row["user"],
						isset($row["password"]) ? $row["password"] : "");
					if (isset($row["features"])) $this->ds()->addFeatures(explode(",", $row["features"]));
					if (isset($row["sync"])) $this->ds()->cc(explode(",", $row["features"]));
					break;
				case "app.stage":
					if ($fname == QModuleStage::dev || $fname == QModuleStage::debug || $fname == QModuleStage::test || $fname == QModuleStage::prod) $this->_stage = $fname;
					else throw new \Exception("Undefined stage: ".$fname);
					break;
				case "app.defmod":
				case "app.defaultmod":
				case "app.defaultmodule":
					$this->_defaultModule = $fname;
					break;
				case "app.outputformat":
					$this->_outputFormat = $fname;
					break;
				case "app.lang":
				case "app.langs":
					$langs = explode(",", $fname);
					$this->_langs = $langs;
					$this->_lang = $langs[0];
					break;
				case "app.timezone": // Example: page.timezone	Europe/Prague
					date_default_timezone_set($fname);
					break;
				case "app.authform": // Example: page.authform	username:loginform-uname	password:loginform-pwd
					$auth = $this->auth();
					if ($auth !== null) $this->auth()->setFormFields($row["username"], $row["password"]);
					else throw new \Exception("Err#xy: Unable to initialize Auth, no DS defined");
					break;
				case "app.cookies": // Allowed cookies
					$arr = explode(",", $fname);
					foreach ($arr as $a) {
						$this->_allowedCookies[] = $a;
						if (isset($_COOKIE[$a])) $this->_args[$a] = $_COOKIE[$a];
					}
					break;
				case "app.route": // Example: page.route	path:products	mod:eshop	func:productlist (will call Eshop->products() for path /products/*)
					if ($page["path"] == $row["path"] || $page["mod"] == $row["path"]) {
						$page["mod"] = $row["mod"];
						if (isset($page["func"])) $this->_args[] = $page["func"];
						$page["func"] = isset($row["func"]) ? $row["func"] : "main";
					}
					break;
			}
		}
		return $page;
	}

	//
	public function loadSubModule($name, $func = null)
	{
		if (file_exists($this->_pathApp."modules/".$name.".php")) {
			require_once $this->_pathApp."modules/".$name.".php";
			$name = "com\\qetrix\\apps\\".$this->appName()."\\modules\\".strToUpper(substr($name, 0, 1)).substr($name, 1);
		} elseif (file_exists($this->_pathAppCommon."modules/".$name.".php")) {
			require_once $this->_pathAppCommon."modules/".$name.".php";
			$name = "com\\qetrix\\apps\\common\\modules\\".strToUpper(substr($name, 0, 1)).substr($name, 1);
		} else throw new \Exception("Err#2021: SubModule \"".$name."\" doesn't exist!");
		$cls = new $name($this);
		if ($func === null) return $cls;
		return $cls->$func();
	}

	/*public function loadModule2($name, $func = "main")
	{
		// Handle keywords, add leading underscore; '/list' in URL => function _list()
		if (isset($func) && in_array($func, array("abstract", "and", "array", "as", "bool", "break", "callable", "case", "catch", "class", "clone", "const", "continue", "declare", "default", "die", "do", "echo", "else", "elseif", "empty", "enddeclare", "endfor", "endforeach", "endif", "endswitch", "endwhile", "eval", "exit", "extends", "false", "final", "float", "for", "foreach", "function", "global", "goto", "if", "implements", "include", "include_once", "int", "instanceof", "insteadof", "interface", "isset", "list", "mixed", "namespace", "new", "null", "numeric", "or", "print", "private", "protected", "public", "require", "require_once", "resource", "return", "scalar", "static", "string", "switch", "throw", "trait", "true", "try", "unset", "use", "var", "while", "xor")))
			$func = "_".$func;

	}*/

	/**
	 * @param array $page
	 *
	 * @link http://wiki.qetrix.com/QPage/loadModule
	 *
	 * @throws \Exception
	 * @return string
	 */
	public function loadModule(array $page)
	{
		// Handle keywords, add trailing underscore; '/list' in URL => function list_(Dict $args)
		$keywords = ["abstract", "and", "array", "as", "bool", "break", "callable", "case", "catch", "class", "clone", "const", "continue", "declare", "default", "die", "do", "echo", "else", "elseif", "empty", "enddeclare", "endfor", "endforeach", "endif", "endswitch", "endwhile", "eval", "exit", "extends", "false", "final", "float", "for", "foreach", "function", "global", "goto", "if", "implements", "include", "include_once", "int", "instanceof", "insteadof", "interface", "isset", "list", "mixed", "namespace", "new", "null", "numeric", "or", "print", "private", "protected", "public", "require", "require_once", "resource", "return", "scalar", "static", "string", "switch", "throw", "trait", "true", "try", "unset", "use", "var", "while", "xor"];
		if (isset($page["mod"]) && in_array($page["mod"], $keywords)) $page["mod"] = $page["mod"]."_";
		$keywords[] = ["ds", "page", "qmodule", "qpage", "trl_ac", "stage"];
		if (isset($page["func"]) && in_array($page["func"], $keywords)) $page["func"] = $page["func"]."_";

		// If DS is defined and allows path searches ('getpath' feature), search it there ONLY, do not use classNames
		if ($this->ds() !== null && !isset($page["dspath"]) && substr(get_class($this->ds()), 0, 16) == "com\\qetrix\\apps\\" && strpos(get_class($this->ds()), "\\datastores\\") !== false && $this->ds()->hasFeature("getpath") && $page["mod"] != "apmin" && $page["mod"] != $this->appName()) {
			$path = $this->ds()->pageUrl($page["path"], $this->appName());
			if ($path !== false) {
				if (isset($page["id"])) $this->_args["0"] = $page["id"];
				$page = array_merge($page, $path);
			} else {
				$page["404"] = $page["mod"] != "" ? $page["mod"] : $page["page"];
				$page["mod"] = "err404";
			}
			$page["dspath"] = $path;
			return $this->loadModule($page);
		} elseif (!isset($page["mod"]) || $page["mod"] == "") $page["mod"] = $this->appName();

		// Load requested PHP script
		$mod = "com\\qetrix\\apps\\".$page["page"]."\\modules\\".strToUpper(substr($page["mod"], 0, 1)).substr($page["mod"], 1);
		$page["mod"] = rtrim($page["mod"], "_");
		if (!class_exists($mod, false)) { // FIXME Custom module "module" (or /module in URL) doesn't work, because Module class exists always
			// Page not found
			if ($page["mod"] == "err404") {
				/* TODO!!! */
				throw new \Exception("Err#2020: Module \"".(isset($page["404"]) ? $page["404"] : $page["mod"])."\" doesn't exist!".($this->ds() === null ? " Did you forget to load a DataStore?" : ""), 20404);

				// App's custom module (QUE)
				/*elseif (class_exists("Que", false) && file_exists($this->dir."modules/".$page["mod"].".que")) {
						echo $this->dir."modules/".$page["mod"].".que";
					} else*/

				// App's custom module (PHP)
			} elseif (file_exists($this->_pathApp."modules/".$page["mod"].".php")) {
				require_once $this->_pathApp."modules/".$page["mod"].".php";

				// General module
			} elseif (file_exists($this->_pathAppCommon."modules/".$page["mod"].".php")) {
				$mod = "com\\qetrix\\apps\\common\\modules\\".strToUpper(substr($page["mod"], 0, 1)).substr($page["mod"], 1);
				require_once $this->_pathAppCommon."modules/".$page["mod"].".php";

				// Mod not found, try default mod
			} elseif ($page["mod"] != $this->_appName && !isset($path["dspath"]) && file_exists($this->_pathApp."/modules/".$this->_appName.".php")) {
				if (isset($page["func"])) array_unshift($this->_args, $page["func"]); /// *MNo 20161103: was $this->_data
				$page["func"] = $page["mod"];
				$page["mod"] = $this->appName();
				return $this->loadModule($page);

				// Very default mod TODO
			} elseif ($this->_defaultModule != "" && !isset($path["dspath"]) && $page["mod"] != $this->_defaultModule) {
				$page["mod"] = $this->_defaultModule;
				unset($page["404"]);
				return $this->loadModule($page);

				// Mod not found, try 404
			} elseif ($page["mod"] != "err404") {
				$page["404"] = $page["mod"];
				$page["mod"] = "err404";
				$this->set("status", "404 Not Found"); // TODO
				// $this->envDS()->set("header", "Referer", "qpage.php"); // -MNo 20151113: Why this?? It's like a debug thing?
				return $this->loadModule($page);

			} else {
				throw new \Exception("Err#2020: Module \"".(isset($page["404"]) ? $page["404"] : $page["mod"])."\" doesn't exist!".($this->ds() === null ? " Did you forget to load a DataStore?" : ""), 20404);
			}
		}

		// Auth user
		if ($this->ds() != null && $this->ds()->hasFeature("u")) {
			if (!$this->auth()->isAuth()) $this->auth()->login($this->getFormData());
		}

		//Util::log($mod);
		/** @var QModule $modClass */
		$modClass = new $mod($this); // Create module object and inject QPage into it
		if ($this->ds() !== null && $this->ds()->hasFeature("l")) $this->_lbl = $this->ds()->getLabels($this->lang());

		if (isset($page["id"]) && $page["id"] > 0) {
			$this->_args["id"] = $page["id"];
			$this->_data["id"] = $page["id"];
		}
		//$this->_data["path"] = $page["path"];

		// Unspecified func => call main
		if (!isset($page["func"]) || $page["func"] == null) {
			$page["func"] = "main";

		} elseif (!method_exists($modClass, $page["func"])) {

			// Func doesn't exists in the class => use func as argument and try to call main, because instead of func it could be just a string param
			if (method_exists($modClass, "main")) {
				array_unshift($this->_args, $page["func"]);
				$page["func"] = "main";

				// Func not found
			} else throw new \Exception("Err#2030: Func \"".$page["func"]."\" in \"".$page["mod"]."\" doesn't exist!");
		}
		$urlX = explode("/", $page["path"]);
		for ($i = 0; $i < count($urlX); $i++) if (is_numeric($urlX[$i])) unset($urlX[$i]);
		$page["xpath"] = implode("/", $urlX);
		if ($this->_auth !== null && $this->ds()->hasFeature("um")) {
			//Util::log($page["mod"].".".$page["func"]." | ".$page["xpath"]);
			$this->auth()->checkAccess($page["mod"], $page["func"]);
		}
		$func = $page["func"];

		if (method_exists($modClass, "init")) $modClass->init(); // Initialize module (using constructor in modules is not quite handy)

		//Util::log($mod.".".$func);
		$output = $modClass->$func(new Dict($this->_args)); // Execute module method and get output. TODO!!! on PROD suppress exception, like when calling xyAction method by URL (because QForm is missing)
		if ($modClass->stage() != QModuleStage::prod && $output == "") throw new \Exception("Err#4001: No output. Did you forget a return statement in ".$page["mod"]."->".$page["func"]."?");
		//$output .= QPage::debug();

		return $output;
	}

	public function loadDataStore($alias, $name, $host, $scope, $prefix = "", $user = "", $password = "")
	{
		$compPath = "datastores/".strToLower($name).".php";
		$scriptPath = (isset($this->_pathApp) && file_exists($this->_pathApp.$compPath) ? $this->_pathApp : $this->path().($this->isMultiApp() ? "apps/common/" : ""));
		$dataStoreClass = "com\\qetrix\\apps\\".($scriptPath == $this->_pathApp ? $this->appName() : "common")."\\datastores\\".$name;
		/** @var DataStore $ds */
		$ds = new $dataStoreClass();
		$ds->conn($host, $scope, $prefix, $user, $password);
		$this->_dsList[$alias !== "" ? $alias : strToLower($name)] = $ds;
		return $ds;
	}

	public function loadConverter($fromFormat, $toFormat, $toType = "")
	{
		//Util::log(get_declared_classes());
		$name = $fromFormat."_".$toFormat.($toType != "" ? "_".$toType : "");
		if (file_exists($this->pathApp()."converters/".strToLower($name).".php")) {
			include_once $this->pathApp()."converters/".strToLower($name).".php";
			$convClass = "com\\qetrix\\apps\\".QPage::getInstance()->appName()."\\converters\\".$name;
			if (!class_exists($convClass, false)) $convClass = "com\\qetrix\\apps\\common\\converters\\".$name;
		} else $convClass = "com\\qetrix\\apps\\common\\converters\\".$name;
		return new $convClass();
	}

	function suggestOutputFormat()
	{
		if ($this->get("_f") != "") {
			$this->_outputFormat = $this->get("_f");
			unset($this->_data["_f"]);
		}
		if (true) return $this->_outputFormat;
		if ($this->get("http_accept") != "") Util::log(explode(",", $this->get("http_accept")), "HTTP_ACCEPT"); // TODO: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 // text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 // application/json
		if ($this->get("http_user_agent") != "") Util::log($this->get("http_user_agent"), "HTTP_USER_AGENT");
		//TODO: Set content type, into output datastore
		return $this->_outputFormat;
	}

	function suggestLanguage()
	{
		if ($this->get("_l") != "") {
			$this->_outputFormat = $this->get("_l");
			unset($this->_data["_l"]);
		}
		if (!$this->get("http_accept_language") != "") return $this->_lang;
		preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $this->get("http_accept_language"), $lang_parse);
		if (count($lang_parse[1])) {
			$langs = array_combine($lang_parse[1], $lang_parse[4]);
			foreach ($langs as $lang => $val) if ($val === "") $langs[$lang] = 1;
			arsort($langs, SORT_NUMERIC);
			foreach ($langs as $lang => $val) return substr(strToLower($lang), 0, 2);
		}
		$this->set("_l", $this->_lang);
		return $this->_lang;
	}

	function lbl($var = false, $translations = null)
	{
		if ($var === false) return isset($this->_lbl["_loaded"]); // Check if lbl() is usable

		if (is_array($var)) {
			$this->_lbl = $var;
			return $this;
		}
		if ($translations !== null) $this->_lbl[$var] = $translations;

		if (isset($this->_lbl[$var]) && isset($this->_lbl[$var]["l".$this->_lang])) return $this->_lbl[$var]["l".$this->_lang];
		$l = explode("-", $var);
		if (count($l) == 2 && isset($this->_lbl[$l[1]])) return $this->_lbl[$l[1]]["l".$this->_lang];
		if (count($l) > 2) {
			if (isset($this->_lbl[$l[0]."-".$l[1]])) return $this->_lbl[$l[0]."-".$l[1]]["l".$this->_lang];
			elseif (isset($this->_lbl[$l[1]."-".$l[2]])) return $this->_lbl[$l[1]."-".$l[2]]["l".$this->_lang];
			elseif (isset($this->_lbl[$l[1]])) return $this->_lbl[$l[1]]["l".$this->_lang];
		}
		if (strpos($var, "_new") > 0) return $this->lbl(str_replace("_new", "", $var));
		return "-".$var."-";
	}

	public function addFeature($feature)
	{
		$this->_features[] = strToLower($feature);
		return $this;
	}

	public function addFeatures(array $features)
	{
		$this->_features = array_merge($this->_features, $features);
		return $this;
	}

	public function hasFeature($featureName)
	{
		return in_array(strToLower($featureName), $this->_features);
	}

	function addMessage($to, $subject = "", $body = "", $from = "", $persistent = false)
	{
		if ($subject == "" && $body == "" && $from == "") {
		}
		$this->_messages[] = [$from, $to, $subject, $body, $persistent];
		/*
		$this->_messages[$scope][] = ["dt" => microtime(true), "msg" => $message];
		//file_put_contents($this->pathAppVars().date("Ymd").".log", date("YmdHis")."\tshutdown. time:".round(microtime(true)-PERF_START_TIME, 2).", mem:".ceil((memory_get_usage(true)-PERF_START_MEM)/1024)."/".ceil(memory_get_peak_usage(true)/1024)."\n\n", FILE_APPEND);
		 */
	}

	// TODO!!!!
	function setAppName($name)
	{
		$this->_appName = $name;
		$this->_pathRes = $this->pathBase()."res/";
		$this->_pathContent = $this->pathBase()."content/";
	}
}

/** QModuleStage enum
 * @link http://wiki.qetrix.com/QModuleStage
 */
abstract class QModuleStage extends QEnum
{
	const debug = 0; // Verbose debug info, enable only if something wents really wrong
	const dev = 1; // Basic debug info, stack trace, default for localhost/dev env
	const test = 2; // No debug info, prints warnings and errors. Like production, with DS mockups for sending e-mails, WS push requests etc.
	const prod = 3; // Production, warnings/error messages are logged, not printed.
}
