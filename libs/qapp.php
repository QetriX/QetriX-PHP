<?php
namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.03.30 | QetriX Application Class
 */

use com\qetrix\libs\components\qlist;
use com\qetrix\libs\components\QView;
use com\qetrix\libs\Util;
use com\qetrix\libs\QModuleStage;
use com\qetrix\apps\common\datastores\Http;

class QApp
{
	private static $_instance; /// Static reference to this QetriX App

	private $_name = ""; /// App Name. Recommended 1-16 chars, [a-z][0-9] only. Used in paths, keys, possibly ds conn etc.
	private $_title = ""; /// Language specific, appropriate to $_name
	private $_auth = null;
	private $_ds = null; /// Default DS, where QType is stored
	private $_stage = "";

	private $_outputFormat = "html"; /// In what format should the output be rendered (what set of converters it uses)
	private $_lang = "en"; /// Current language, English as a default language, incl. internationalization and localization (g11n) settings: date format, time format, units in general, temperature...
	private $_langs = array("en"); /// List of available languages
	private $_lbl = []; /// Array of labels in current language

	/** @var Http null */
	private $_envDS;

	private $_dsList;

	private $_path; /// PHP ROOT DIR	D:\qetrix\ (base dir, PHP custom)
	private $_pathApp; /// PHP APP DIR		D:\qetrix\apps\test\ (app dir, PHP custom)
	private $_pathAppCommon; /// Common App Path
	private $_pathData;
	private $_pathContent; // Upload dir for resources

	private $_isMultiApp; /// Uses this QetriX multi-app mode? (looks for /apps/ subdir in QetriX root dir)
	private $defaultModule = ""; /// What module should be used as default, when requested module wasn't found

	/** PHP constructor */
	function __construct()
	{
		$this->QApp();
	}

	/** Class constructor */
	function QApp()
	{
		// Get the start time and memory, e.g. for performance monitoring
		define("PERF_START_TIME", microtime(true));
		define("PERF_START_MEM", memory_get_usage());

		/// To use $this in closures
		$self = $this;

		/// Define custom error handler
		$errHandler = function ($errno, $errstr, $errfile, $errline) use ($self) {
			QApp::error($self, $errstr." (".$errfile.":".$errline."), error", debug_backtrace());
		};
		set_error_handler($errHandler);

		/// Define custom exception handler
		$exHandler = function ($ex) use ($self) {
			QApp::error($self, $ex->getMessage(), $ex->getTrace());
		};
		set_exception_handler($exHandler);

		/// Set every encoding to UTF-8
		mb_internal_encoding("UTF-8");
		mb_http_output("UTF-8");
		mb_http_input("UTF-8");
		mb_language("uni");
		mb_regex_encoding("UTF-8");

		/// QetriX uses UTC TZ
		date_default_timezone_set("UTC");

		/// Initialize core variables
		$this->_path = substr(__DIR__, 0, -4); // PHP BASE (ROOT) DIR
		$this->_isMultiApp = is_dir($this->path()."apps");

		/// Load environment datastore
		$this->_envDS = $this->loadDataStore("Http", "", "get");
		$this->envDS()->set("header", "Expires", "Thu, 19 Nov 2009 14:35:26 GMT");
		$this->envDS()->set("header", "Cache-Control", "no-cache, no-store, max-age=0, must-revalidate");
		$this->envDS()->set("header", "Pragma", "no-cache");

		/// Initialize system variables
		$this->_outputFormat = $this->suggestOutputFormat();
		$this->_lang = $this->suggestLanguage();

		include_once $this->path()."libs/util.php";
		require_once $this->path()."libs/components/qform.php";
		require_once $this->path()."libs/components/qlist.php";
		require_once $this->path()."libs/components/qview.php";
	}

	static function getInstance()
	{
		if (is_null(self::$_instance)) self::$_instance = new self;
		return self::$_instance;
	}

	static function error($self, $message, $trace)
	{
		if ($self->envDS() === null) {
			echo "System Error: ".$message."\n";
			exit(1);
		}
		if ($self->_name == "") $self->_name = "common";
		$self->envDS()->set("header", "HTTP/1.1 500 Internal Server Error", "");
		$exv = new QView("ex", "Exception");
		$exv->add($message);

		$stc = new QList("stc", "Stack Trace");
		foreach ($trace as $exl) {
			if (!isset($exl["file"])) continue;
			$stc->add(array("file" => $exl["file"], "line" => $exl["line"], "function" => $exl["function"]));
		}
		$exv->add(Util::convert($stc, "table"));

		$envScopes = $self->envDS()->scopes();
		foreach ($envScopes as $scope) {
			$scopeArr = $self->envDS($scope);
			if (count($scopeArr) > 0) {
				$scp = new QList("scp".$scope, $scope);
				foreach ($scopeArr as $k => $v) $scp->add(array("name" => $k, "value" => $v));
				$exv->add(Util::convert($scp, "table"));
			}
		}
		$self->envDS()->output(Util::convert($exv, "page"));
		exit(1);
	}

	public function envDS($scope = null, $key = null)
	{
		if ($scope === null) return $this->_envDS;
		if ($key === null) return $this->_envDS->get($scope);
		return $this->_envDS->get($scope, $key);
	}

	public function pathApp()
	{
		return $this->_pathApp;
	}

	public function pathAppCommon()
	{
		return $this->_pathAppCommon;
	}

	/** PHP's data path */
	public function pathData()
	{
		return $this->_pathData;
	}

	/** PHP's content path */
	public function pathContent()
	{
		return $this->_pathContent;
	}

	/** PHP's root path */
	public function path()
	{
		return $this->_path;
	}

	public function name()
	{
		return $this->_name;
	}

	public function title()
	{
		return $this->_title;
	}

	public function outputFormat()
	{
		return $this->_outputFormat;
	}

	public function lang()
	{
		return $this->_lang;
	}

	/** Primary DataStore */
	public function ds($value = null)
	{
		if ($value === null) return $this->_ds;
		$this->_ds = $value;
		return $this;
	}

	/** @return Auth */
	public function auth()
	{
		if ($this->_auth === null && $this->ds() !== null) {
			include_once $this->path()."libs/auth.php";
			$this->_auth = new Auth($this->ds());
		}

		return $this->_auth;
	}

	public function isMultiApp() /// The only usage so far is in HTTP DS
	{
		return $this->_isMultiApp;
	}

	public function parsePath($path)
	{
		if ($this->name() != "") throw new \Exception("Err#0101: App has already been loaded.");

		$aPath = explode("/", trim($path, "/"));
		/*if ($aPath[0] == "") array_shift($aPath);
		if (count($aPath) > 0 && $aPath[count($aPath) - 1] == "") array_pop($aPath);*/

		$modVars = ["args" => []];

		// Set the App
		if ($this->_isMultiApp) {
			$modVars["app"] = array_shift($aPath);
			$this->_pathAppCommon = $this->_path."apps".DIRECTORY_SEPARATOR."common".DIRECTORY_SEPARATOR;

			if ($modVars["app"] === null) {
				if ($this->envDS("env", "localhost") == "1") { // localhost + multiapp
					$fsds = $this::loadDataStore("filesystem", "", "", "");
					die(Util::convert($fsds->listApps()));
				}
				throw new \Exception("Undefined App!");
			}
			$this->_name = $modVars["app"];
			$this->_pathApp = $this->_path."apps".DIRECTORY_SEPARATOR.$this->_name.DIRECTORY_SEPARATOR;
		} else {
			$modVars["app"] = "";
			$this->_pathApp = $this->_path;
			$this->_pathAppCommon = $this->_path."apps".DIRECTORY_SEPARATOR."common".DIRECTORY_SEPARATOR;
		}
		$this->_pathContent = $this->pathApp()."content/";
		$this->_pathData = $this->pathApp()."data/";

		if (!file_exists($this->pathApp())) throw new \Exception("App ".$this->name()." not found! Misspelled?");
		if (file_exists($this->pathApp()."app.que")) {
			$cfgData = explode("\n", str_replace("\r", "", file_get_contents($this->_pathApp."app.que")));

			// Process config file lines
			for ($rowNum = 0; $rowNum < count($cfgData); $rowNum++) {
				if (trim($cfgData[$rowNum]) == "") continue;
				$row = Util::getSettings($cfgData[$rowNum], "\t");
				$fname = null;
				$func = null;
				foreach ($row as $func => $fname) break; // Get first key/value (which is func in key and possibly it's name in value)
				if (substr($func, 0, 2) == "//") continue;

				switch (strToLower($func)) {
					case "app.name":
						$this->_name = $fname;
						$modVars["app"] = $fname;
						$this->envDS()->activate($fname);
						break;
					case "app.title":
						$this->_title = $fname;
						break;
					case "app.ds":
						if (isset($row["env"]) && $this->envDS("env", "server_name") != $row["env"]) continue;
						$this->_ds = $this->loadDataStore($fname,
							$row["host"],
							isset($row["scope"]) ? $row["scope"] : $this->name(),
							isset($row["prefix"]) ? $row["prefix"] : "",
							$row["user"],
							isset($row["password"]) ? $row["password"] : "");
						if (isset($row["features"])) $this->ds()->addFeatures(explode(",", $row["features"]));
						if (isset($row["sync"])) $this->ds()->sync(explode(",", $row["features"]));
						break;
					case "app.stage":
						$this->_stage = $fname;
						break;
					case "app.defaultmod":
						$this->defaultModule = $fname;
						break;
					case "app.outputformat":
						$this->_outputFormat = $fname;
						break;
					case "app.lang":
						$langs = explode(",", $fname);
						$this->_langs = $langs;
						$this->_lang = $langs[0];
						break;
					case "app.g11n":
						$this->_g11n = $fname;
						break;
					case "app.timezone":
						date_default_timezone_set($fname);
						break;
					case "app.authform":
						$this->auth()->setFormFields($row["username"], $row["password"]);
						break;
				}
			}
		} else require_once $this->pathApp()."config.php";

		if ($this->name() == "") $this->_name = "qetrix"; // If not defined, set app name to "qetrix"

		// Load config
		$modVars["path"] = implode("/", $aPath); // without app and stuff

		// Mod
		if (isset($aPath[0]) && is_numeric($aPath[0])) $modVars["id"] = array_shift($aPath);
		else $modVars["mod"] = array_shift($aPath);
		if (!isset($modVars["mod"]) || (isset($modVars["mod"]) && in_array($modVars["mod"], array(null, "module")))) $modVars["mod"] = $modVars["app"];

		if (count($aPath) > 0) {
			// Func
			if (is_numeric($aPath[0])) {
				if (isset($modVars["id"])) {
					$modVars["args"][] = array_shift($aPath);
				} else {
					$modVars["id"] = array_shift($aPath);
					$modVars["func"] = array_shift($aPath);
				}
			} else {
				$modVars["func"] = array_shift($aPath);
				if (count($aPath) > 0 && !isset($modVars["id"]) && is_numeric($aPath[0])) $modVars["id"] = array_shift($aPath);
			}
			if (isset($modVars["func"])) $modVars["func"] = str_replace("-", "_", $modVars["func"]);

			// Args
			$modVars["args"] = array_merge($modVars["args"], $aPath);
		}

		if (isset($modVars["mod"]) && $modVars["mod"] == "content") include_once $this->path()."libs/file.php";
		return $modVars;
	}

	//
	public function loadSubModule($name, $func = null, $args = null)
	{
		if (file_exists($this->pathApp()."modules/".$name.".php")) {
			require_once $this->pathApp()."modules/".$name.".php";
			$name = "com\\qetrix\\apps\\".$this->name()."\\modules\\".strToUpper(substr($name, 0, 1)).substr($name, 1);
		} elseif (file_exists($this->pathAppCommon()."modules/".$name.".php")) {
			require_once $this->pathAppCommon()."modules/".$name.".php";
			$name = "com\\qetrix\\apps\\common\\modules\\".strToUpper(substr($name, 0, 1)).substr($name, 1);
		} else throw new \Exception("Err#2021: SubModule \"".$name."\" doesn't exist!");
		$cls = new $name($this);
		if ($func === null) return $cls;
		return $cls->$func($args);
	}

	public function loadModule($page)
	{
		// Handle PHP keywords, add leading underscore; '/list' in URL => function _list($args)
		if (isset($page["func"]) && in_array($page["func"], array("abstract", "and", "array", "as", "bool", "break", "callable", "case", "catch", "class", "clone", "const", "continue", "declare", "default", "die", "do", "echo", "else", "elseif", "empty", "enddeclare", "endfor", "endforeach", "endif", "endswitch", "endwhile", "eval", "exit", "extends", "false", "final", "float", "for", "foreach", "function", "global", "goto", "if", "implements", "include", "include_once", "int", "instanceof", "insteadof", "interface", "isset", "list", "mixed", "namespace", "new", "null", "numeric", "or", "print", "private", "protected", "public", "require", "require_once", "resource", "return", "scalar", "static", "string", "switch", "throw", "trait", "true", "try", "unset", "use", "var", "while", "xor")))
			$page["func"] = "_".$page["func"];

		// If DS is defined and allows path searches ('getpath' feature), search it there ONLY, do not use classNames
		if ($this->ds() !== null && !isset($page["dspath"]) && substr(get_class($this->ds()), 0, 16) == "com\\qetrix\\apps\\" && strpos(get_class($this->ds()), "\\datastores\\") !== false && $this->ds()->hasFeature("getpath") && $page["mod"] != "apmin" && $page["mod"] != $this->name()) {
			$path = $this->ds()->pageUrl($page["path"]);
			if ($path !== false) {
				$page = array_merge($page, $path);
			} else {
				$page["404"] = $page["mod"] != "" ? $page["mod"] : $page["app"];
				$page["mod"] = "err404";
			}
			$page["dspath"] = $path;
			return $this->loadModule($page);
		} elseif (!isset($page["mod"]) || $page["mod"] == "") $page["mod"] = $this->name();

		// Load requested PHP script
		require_once $this->path()."libs/qmodule.php";
		$mod = "com\\qetrix\\apps\\".$page["app"]."\\modules\\".strToUpper(substr($page["mod"], 0, 1)).substr($page["mod"], 1);
		if (!class_exists($mod)) { // FIXME Custom module "module" (or /module in URL) doesn't work, because Module class exists always
			// Page not found
			if ($page["mod"] == "err404") {
				/* TODO!!! */
				throw new \Exception("Err#2020: Module \"".(isset($page["404"]) ? $page["404"] : $page["mod"])."\" doesn't exist!".($this->_ds === null ? " Did you forget to load a DataStore?" : ""), 20404);

				// App's custom module (QUE)
				/*elseif (class_exists("Que") && file_exists($this->dir."modules/".$page["mod"].".que")) {
						echo $this->dir."modules/".$page["mod"].".que";
					} else*/

				// App's custom module (PHP)
			} elseif (file_exists($this->pathApp()."modules/".$page["mod"].".php")) {
				require_once $this->pathApp()."modules/".$page["mod"].".php";

				// General module
			} elseif (file_exists($this->pathAppCommon()."modules/".$page["mod"].".php")) {
				$mod = "com\\qetrix\\apps\\common\\modules\\".strToUpper(substr($page["mod"], 0, 1)).substr($page["mod"], 1);
				require_once $this->pathAppCommon()."modules/".$page["mod"].".php";

				// Mod not found, try default mod
			} elseif ($page["mod"] != $this->_name && !isset($path["dspath"]) && file_exists($this->pathApp()."/modules/".$this->_name.".php")) {
				if (isset($page["func"])) array_unshift($page["args"], $page["func"]);
				$page["func"] = $page["mod"];
				$page["mod"] = $this->name();
				return $this->loadModule($page);

				// Very default mod TODO
			} elseif ($this->defaultModule != "" && !isset($path["dspath"]) && $page["mod"] != $this->defaultModule) {
				$page["mod"] = $this->defaultModule;
				unset($page["404"]);
				return $this->loadModule($page);

				// Mod not found, try 404
			} elseif ($page["mod"] != "err404") {
				$page["404"] = $page["mod"];
				$page["mod"] = "err404";
				$this->envDS()->set("header", $this->envDS()->get("env", "server_protocol")." 404 Not Found", "");
				// $this->envDS()->set("header", "Referer", "qapp.php"); // -MNo 20151113: Why this?? It's like a debug thing?
				return $this->loadModule($page);

			} else {
				throw new \Exception("Err#2020: Module \"".(isset($page["404"]) ? $page["404"] : $page["mod"])."\" doesn't exist!".($this->_ds === null ? " Did you forget to load a DataStore?" : ""), 20404);
			}
		}
		//$this->envDS()->activate($this->name()); -MNo 20160418

		// Auth user
		if ($this->ds() != null && $this->ds()->hasFeature("u")) {
			// Login: make QForm called "login" with "username" and "password" QFormControls, optionally hidden "goto" QFormControl with relative path for redirect
			if (!$this->auth()->isAuth() && $this->auth()->login($this->envDS("post"))) {
				//$auth = $this->auth()->login($this->envDS("post", "login-username"), $this->envDS("post", "login-password"));
				//$auth = $this->auth()->login($this->envDS("post"));
				if ($this->envDS("post", "login-goto") != "") {
					die("autologin");
					$this->envDS()->set("header", "location", $this->envDS("env", "pathFull").$this->envDS("post", "login-goto"));
					die;
				}
			}

		}

		// Add the rest of $page variables
		//$page["mod"] = $mod;
		$page["modClass"] = new $mod($this); // Create module object
		//$page["modClass"]->ds = $this->_ds;
		if ($this->envDS()->get("get", "_f") != "") $page["modClass"]->outputFormat = $this->envDS()->get("get", "_f");
		if ($this->ds() !== null && $this->ds()->hasFeature("l")) $this->_lbl = $this->ds()->getLabels($this->lang());

		if (isset($page["id"]) && $page["id"] > 0) $page["args"]["id"] = $page["id"];
		$page["args"]["path"] = $page["path"];

		// Unspecified func => call main
		if (!isset($page["func"]) || $page["func"] == null) {
			$page["func"] = "main";

		} elseif (!method_exists($page["modClass"], $page["func"])) {

			// Func doesn't exists in the class => use func as argument and try to call main, because instead of func it could be just a string param
			if (method_exists($page["modClass"], "main")) {
				array_unshift($page["args"], $page["func"]);
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
		if (isset($page["pid"])) $page["args"]["pid"] = $page["pid"]; //+MNo 20160417

		$output = $page["modClass"]->$func($page["args"]); // Execute module method and get output

		if ($page["modClass"]->stage != QModuleStage::prod && $output == "") throw new \Exception("Err#4001: No output. Did you forget a return statement in ".$page["mod"]."->".$page["func"]."?");
		return $output;
	}

	/*function flog($text, $level = 5) // File Log
	{
		// TODO: No, this should be in Messager!!!
		file_put_contents($this->path."exps".DIRECTORY_SEPARATOR.APP.".log", substr_replace(date("c"), substr(microtime(), 1, 4), 19, 0)."   ".$text."\n", FILE_APPEND);
	}*/

	function loadConfig()
	{
		$confArray = explode("\n", str_replace("\r", "", file_get_contents($this->pathApp().".php")));
		foreach ($confArray as $conf) {
		}
	}

	function loadDataStore($dataStoreClassName, $host, $db, $prefix = "", $user = null, $password = null)
	{
		require_once $this->path()."libs/datastore.php";
		$compPath = "datastores/".strToLower($dataStoreClassName).".php";
		$scriptPath = (isset($this->_pathApp) && file_exists($this->pathApp().$compPath) ? $this->pathApp() : $this->path().($this->isMultiApp() ? "apps/common/" : ""));
		if (!file_exists($scriptPath.$compPath)) throw new \Exception("Err#xy: DataStore ".$dataStoreClassName." not found!"); // TODO: Disable for PROD for performance
		require_once $scriptPath.$compPath;
		$dataStoreClass = "com\\qetrix\\apps\\".($scriptPath == $this->pathApp() ? $this->name() : "common")."\\datastores\\".$dataStoreClassName;
		$c = new $dataStoreClass($this);
		$c->conn($host, $db, $prefix, $user, $password);
		$this->_dsList[strToLower($dataStoreClassName)] = $c;
		return $c;
	}

	// Entire App should use the same output format
	function suggestOutputFormat()
	{
		if ($this->envDS("get", "_f") != "") $this->_outputFormat = $this->envDS("get", "_f");
		if (true) return $this->_outputFormat;
		//vd($_SERVER);
		if ($this->envDS("env", "http_accept") != "") Util::log(explode(",", $this->envDS("env", "http_accept")), "HTTP_ACCEPT"); // TODO: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 // text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 // application/json
		if ($this->envDS("env", "http_user_agent") != "") Util::log($this->envDS("env", "http_user_agent"), "HTTP_USER_AGENT");
		//TODO: Read GET/QUERY_STRING variables: ?_f=json
		//TODO: Set content type, into output datastore
		return $this->_outputFormat;
	}

	// Entire App should use the same language
	function suggestLanguage()
	{
		// TODO: How the App knows what languages are present?!
		if (!$this->envDS("env", "http_accept_language") != "") return $this->_lang;
		preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $this->envDS("env", "http_accept_language"), $lang_parse);
		if (count($lang_parse[1])) {
			$langs = array_combine($lang_parse[1], $lang_parse[4]);
			foreach ($langs as $lang => $val) if ($val === "") $langs[$lang] = 1;
			arsort($langs, SORT_NUMERIC);
			foreach ($langs as $lang => $val) return substr(strToLower($lang), 0, 2);
		}
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

		if (isset($this->_lbl[$var])) return $this->_lbl[$var]["l".$this->_lang];
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
}
