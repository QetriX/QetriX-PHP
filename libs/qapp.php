<?php namespace com\qetrix\libs;

/* Copyright (c) 2015 QetriX. Licensed under MIT License, see /LICENSE.txt file.
 * QApp Class
 */

use com\qetrix\components\qlist;
use com\qetrix\components\QView;
use com\qetrix\datastores\Http;
use com\qetrix\libs\Que;
use com\qetrix\libs\Util;
use com\qetrix\modules\QModuleStage;

class QApp
{
	private static $_instance; /// Static reference to this QetriX App

	private $_name = ""; /// App Name. Recommended 1-16 chars, [a-z][0-9] only. Used in paths, keys, possibly ds conn etc.
	private $_title = ""; /// Language specific, appropriate to $_name
	private $_username = null; /// Username of currently logged-in user
	private $_ds = null; /// Default DS, where QType is stored
	private $_stage = "";

	private $_outputFormat = "html"; /// In what format should the output be rendered (what set of converters it uses)
	private $_lang = "en"; /// English as a default language
	private $_g11n; /// Internationalization and localization settings: date format, time format, units in general, temperature...
	private $_lbl = []; /// Array of labels in current language

	/** @var Http null */
	private $envDS;

	private $_path; /// PHP ROOT DIR	D:\qetrix\ (base dir, PHP custom)
	private $_pathApp; /// PHP APP DIR		D:\qetrix\apps\test\ (app dir, PHP custom)
	private $_pathData;
	private $_pathContent; // Upload dir for resources

	private $multiApp; /// Uses this QetriX multi-app mode? (looks for /apps/ subdir in QetriX root dir)
	private $defaultModule = ""; /// What module should be used as default, when requested module wasn't found
	private $_userSpaces = false; /// Uses this QetriX user spaces? (username in path)

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

		// Define custom error handler
		$errHandler = function ($errno, $errstr, $errfile, $errline) use ($self) {
			if (!(error_reporting() & $errno)) return;
			if ($errno != E_USER_NOTICE) throw new \Exception($errstr." (".$errfile.":".$errline.")", $errno);
		};
		set_error_handler($errHandler);

		// Define custom exception handler
		$exHandler = function (\Exception $ex) use ($self) {
			if ($this->envDS() === null) die("Fatal Error: ".$ex->getMessage());
			$this->envDS()->set("header", "HTTP/1.1 500 Internal Server Error", "");
			$exv = new QView("ex", "Exception");
			$exv->add($ex->getMessage());

			$stc = new QList("stc", "Stack Trace");
			foreach ($ex->getTrace() as $ex) $stc->add(array("file" => $ex["file"], "line" => $ex["line"], "function" => $ex["function"]));
			$exv->add($self->convert($stc, "table"));

			$envScopes = $this->envDS()->scopes();
			foreach ($envScopes as $scope) {
				$scopeArr = $this->envDS($scope);
				if (count($scopeArr) > 0) {
					$scp = new QList("scp".$scope, $scope);
					foreach ($scopeArr as $k => $v) $scp->add(array("name" => $k, "value" => $v));
					$exv->add($self->convert($scp, "table"));
				}
			}
			$this->envDS()->output($self->convert($exv, "page"));
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

		$this->_path = substr(__DIR__, 0, -4); // PHP BASE (ROOT) DIR
		$this->multiApp = is_dir($this->path()."apps");

		$this->envDS = $this->loadDataStore("Http", "", "get");
		$this->envDS()->set("header", "Expires", "Thu, 19 Nov 2009 14:35:26 GMT");
		$this->envDS()->set("header", "Cache-Control", "no-cache, no-store, max-age=0, must-revalidate");
		$this->envDS()->set("header", "Pragma", "no-cache");

		$this->_outputFormat = $this->suggestOutputFormat();
		$this->_lang = $this->suggestLanguage();

		include_once $this->path()."libs/util.php";
		require_once $this->path()."components/component.php";
		require_once $this->path()."components/qform.php";
		require_once $this->path()."components/qlist.php";
		require_once $this->path()."components/qview.php";
	}

	static function getInstance()
	{
		if (is_null(self::$_instance)) self::$_instance = new self;
		return self::$_instance;
	}

	public function envDS($scope = null, $key = null)
	{
		if ($scope === null) return $this->envDS;
		if ($key === null) return $this->envDS->get($scope);
		return $this->envDS->get($scope, $key);
	}

	public function pathApp()
	{
		return $this->_pathApp;
	}

	public function pathData()
	{
		return $this->_pathData;
	}

	public function pathContent()
	{
		return $this->_pathContent;
	}

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

	public function username()
	{
		return $this->_username;
	}

	public function outputFormat()
	{
		return $this->_outputFormat;
	}

	public function lang()
	{
		return $this->_lang;
	}

	public function ds($value = null)
	{
		if ($value === null) return $this->_ds;
		$this->_ds = $value;
		return true;
	}

	public function isMultiApp() // The only usage so far is in HTTP DS
	{
		return $this->multiApp;
	}

	public function hasUserSpaces()
	{
		return $this->_userSpaces;
	}

	public function getModule($path)
	{
		if ($this->name() != "") throw new \Exception("Err#0101: App has been already loaded.");

		$aPath = explode("/", $path);
		if ($aPath[0] == "") array_shift($aPath);
		if ($aPath[count($aPath) - 1] == "") array_pop($aPath);

		$modVars = array("args" => array());

		// Set the APp
		if ($this->multiApp) {
			$modVars["app"] = array_shift($aPath);
			if ($modVars["app"] === null) {
				if ($this->envDS("env", "remote_addr") == "127.0.0.1" || $this->envDS("env", "remote_addr") == "::1") { // localhost + multiapp
					$apps = scandir($this->path()."apps/");
					require_once $this->_path."/components/qlist.php"; // TODO: Why needed here???
					$appList = new QList("appList");
					$appList->addCol("text");
					foreach ($apps as $app) if ($app == "." || $app == "..") continue; elseif (is_dir($this->path()."apps/".$app) && (file_exists($this->path()."/apps/".$app."/config.php") || file_exists($this->path()."/apps/".$app."/app.que"))) $appList->add(array("text" => $app));
					die($this->convert($appList));
				}
				throw new \Exception("Undefined App!");
			}
			$this->_name = $modVars["app"];
			$this->_pathApp = $this->_path."apps".DIRECTORY_SEPARATOR.$this->_name.DIRECTORY_SEPARATOR;
		} else {
			$modVars["app"] = "";
			$this->_pathApp = $this->_path;
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
				foreach ($row as $func => $fname) break; // Get first key/value (which is func in key and possibly it's name in value)
				if (substr($func, 0, 2) == "//") continue;

				switch (strToLower($func)) {
					case "app.name": $this->_name = $fname; break;
					case "app.title": $this->_title = $fname; break;
					case "app.ds":
						$this->_ds = $this->loadDataStore($fname,
							$row["host"],
							isset($row["scope"]) ? $row["scope"] : $this->name(),
							isset($row["prefix"]) ? $row["prefix"] : "",
							$row["user"],
							isset($row["password"]) ? $row["password"] : "");
						if (isset($row["features"])) $this->_ds->addFeatures(explode(",", $row["features"]));
						if (isset($row["sync"])) $this->_ds->sync(explode(",", $row["features"]));
						break;
					case "app.stage": $this->_stage = $fname; break;
					case "app.defaultmod": $this->defaultModule = $fname; break;
					case "app.outputformat": $this->_outputFormat = $fname; break;
					case "app.lang": $this->_lang = $fname; break;
					case "app.g11n": $this->_g11n = $fname; break;
					case "app.timezone": date_default_timezone_set($fname); break;
				}
				/*Util::log($row);
				Util::log($fname, $func);*/
			}

			/*require_once __DIR__."/que.php";
			$config = (new Que($this, "app"))->parse();*/
			//Util::log($cfgData);
		} else require_once $this->pathApp()."config.php";

		if ($this->name() == "") $this->_name = "qetrix"; // Name has to be defined
		//if ($modVars["app"] == "") $modVars["app"] = APP; // -MNo:20150403
		if ($this->hasUserSpaces()) $this->_pathContent .= $this->username()."/";

		// Load config
		$modVars["path"] = implode("/", $aPath); // without app and stuff

		// Mod
		if (is_numeric($aPath[0])) $modVars["pid"] = array_shift($aPath);
		else $modVars["mod"] = array_shift($aPath);
		if (in_array($modVars["mod"], array(null, "module"))) $modVars["mod"] = $modVars["app"];

		if (count($aPath) > 0) {
			// Func
			if (is_numeric($aPath[0])) {
				if (isset($modVars["pid"])) {
					$modVars["args"][] = array_shift($aPath);
				} else {
					$modVars["pid"] = array_shift($aPath);
					$modVars["func"] = array_shift($aPath);
				}
			} else {
				$modVars["func"] = array_shift($aPath);
				if (count($aPath) > 0 && !isset($modVars["pid"]) && is_numeric($aPath[0])) $modVars["pid"] = array_shift($aPath);
			}
			$modVars["func"] = str_replace("-", "_", $modVars["func"]);
			// Args
			$modVars["args"] = array_merge($modVars["args"], $aPath);
		}

		if ($modVars["mod"] == "content") include_once $this->path()."libs/file.php";

		return $modVars;
	}

	//
	public function loadSubModule($name, $func = null, $args = null)
	{
		if (file_exists($this->pathApp()."modules/".$name.".php")) require_once $this->pathApp()."modules/".$name.".php";
		elseif (file_exists($this->path()."modules/".$name.".php")) require_once $this->path()."modules/".$name.".php";
		else throw new \Exception("Err#2021: SubMod \"".$name."\" doesn't exist!");
		$name = "com\\qetrix\\modules\\".$name;
		$cls = new $name($this);
		if ($func === null) return $cls;
		return $cls->$func($args);
	}

	public function loadModule($page)
	{
		// Handle PHP keywords, add leading underscore; /list => function _list($args)
		if (in_array($page["func"], array("abstract", "and", "array", "as", "bool", "break", "callable", "case", "catch", "class", "clone", "const", "continue", "declare", "default", "die", "do", "echo", "else", "elseif", "empty", "enddeclare", "endfor", "endforeach", "endif", "endswitch", "endwhile", "eval", "exit", "extends", "false", "final", "float", "for", "foreach", "function", "global", "goto", "if", "implements", "include", "include_once", "int", "instanceof", "insteadof", "interface", "isset", "list", "mixed", "namespace", "new", "null", "numeric", "or", "print", "private", "protected", "public", "require", "require_once", "resource", "return", "scalar", "static", "string", "switch", "throw", "trait", "true", "try", "unset", "use", "var", "while", "xor")))
			$page["func"] = "_".$page["func"];

		// Load requested PHP script
		require_once $this->path()."modules/qmodule.php";
		$mod = "com\\qetrix\\modules\\".strToUpper(substr($page["mod"], 0, 1)).substr($page["mod"], 1);
		//flog('LoadPage: '.$mod, 5);
		if (!class_exists($mod)) { // FIXME Custom module "module" (or /module in URL) doesn't work, because Module class exists always
			// App's custom module (QUE)
			/*if (class_exists("Que") && file_exists($this->dir."modules/".$page["mod"].".que")) {
				echo $this->dir."modules/".$page["mod"].".que";
			} else*/

			// App's custom module (PHP)
			if (file_exists($this->pathApp()."modules/".$page["mod"].".php")) {
				require_once $this->pathApp()."modules/".$page["mod"].".php";

				// General module
			} elseif (file_exists($this->path()."modules/".$page["mod"].".php")) {
				require_once $this->path()."modules/".$page["mod"].".php";

				// If DS is defined and allows path searches (getpath feature), search it there
			} elseif ($this->ds() !== null && substr(get_class($this->ds()), 0, 21) == "com\\qetrix\\datastores" && $this->ds()->hasFeature("getpath") && ($path = $this->ds()->pageUrl($page["path"])) !== false) {
				if ($path["mod"] == $page["mod"]) throw new \Exception("Err#2020: Mod \"".$page["mod"]."\" doesn't exist!".($this->_ds === null ? " Did you forget to a load DataStore?" : ""), 20404);
				$page = array_merge($page, $path);
				return $this->loadModule($page);

				// Mod not found, try default mod
			} elseif ($page["mod"] != $this->_name && file_exists($this->pathApp()."/modules/".$this->_name.".php")) {
				if (isset($page["func"])) array_unshift($page["args"], $page["func"]);
				$page["func"] = $page["mod"];
				$page["mod"] = $this->_name;
				return $this->loadModule($page);

				// Very default mod TODO
			} elseif ($this->defaultModule != "" && $page["mod"] != $this->defaultModule) {
				$page["mod"] = $this->defaultModule;
				unset($page["404"]);
				return $this->loadModule($page);

				// Mod not found, try 404
			} elseif ($page["mod"] != "err404") {
				$page["404"] = $page["mod"];
				$page["mod"] = "err404";
				$this->envDS()->set("header", $this->envDS()->get("env", "server_protocol")." 404 Not Found", "");
				$this->envDS()->set("header", "Referer", "qapp.php");
				return $this->loadModule($page);

			} else {
				throw new \Exception("Err#2020: Mod \"".(isset($page["404"]) ? $page["404"] : $page["mod"])."\" doesn't exist!".($this->_ds === null ? " Did you forget to load a DataStore?" : ""), 20404);
			}
		}
		$this->envDS()->activate($this->name());

		// Add the rest of $page variables
		$page["mod"] = $mod;
		$page["modClass"] = new $page["mod"]($this); // Create module object
		$page["modClass"]->ds = $this->_ds;
		if ($this->envDS()->get("get", "_f") != "") $page["modClass"]->outputFormat = $this->envDS()->get("get", "_f");
		if ($this->ds() !== null && $this->ds()->hasFeature("l")) $this->_lbl = $this->_ds->getLabels($this->lang());

		if (isset($page["pid"]) && $page["pid"] > 0) $page["args"]["pid"] = $page["pid"];
		$page["args"]["path"] = $page["path"];

		// Unspecified func => call main
		if ($page["func"] == null) {
			$page["func"] = "main";

		} elseif (!method_exists($page["modClass"], $page["func"])) {

			// Func doesn't exists in the class => use func as argument and try to call main, because instead of func it could be just a string param
			if (method_exists($page["modClass"], "main")) {
				array_unshift($page["args"], $page["func"]);
				$page["func"] = "main";

				// Func not found
			} else throw new \Exception("Err#2030: Func \"".$page["func"]."\" in \"".$page["mod"]."\" doesn't exist!");
		}

		//flog($page["mod"]."->".$page["func"]."(args)", 1);
		$output = $page["modClass"]->$page["func"]($page["args"]); // Execute module method and get output

		if ($page["modClass"]->stage != QModuleStage::Prod && $output == "") throw new \Exception("Err#4001: No output. Did you forget a return statement in ".$page["mod"]."->".$page["func"]."?");
		// if (is_object($output)) return $this->convert($output, $page["modClass"]->outputFormat); -20150413 MNo: There's no App->convert()
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
		require_once $this->path()."datastores/datastore.php";
		$compPath = "datastores/".strToLower($dataStoreClassName).".php";
		//flog('LoadDataStore: '.$compPath, 5);
		if (!file_exists((isset($this->_pathApp) && file_exists($this->pathApp().$compPath) ? $this->pathApp() : $this->path()).$compPath)) throw new \Exception("DataStore ".$dataStoreClassName." not found!"); // TODO: Disable for PROD for performance
		require_once (isset($this->_pathApp) && file_exists($this->pathApp().$compPath) ? $this->pathApp() : $this->path()).$compPath;
		$dataStoreClass = "com\\qetrix\\datastores\\".$dataStoreClassName;
		$c = new $dataStoreClass($this);
		$c->conn($host, $db, $prefix, $user, $password);
		return $c;
	}

	public function userLogin($username, $password = false)
	{
		// username may be token, if password is false and legnth is the same as UUID
		$this->_username = $username;
	}

	// Entire App should use the same output format
	function suggestOutputFormat()
	{
		if ($this->envDS("get", "_f") != "") $this->_outputFormat = $this->envDS("get", "_f");
		if (true) return $this->_outputFormat;
		//vd($_SERVER);
		if ($this->envDS("env", "http_accept") != "") Util::log(explode(",", $this->envDS("env", "http_accept")), "HTTP_ACCEPT"); // text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
		if ($this->envDS("env", "http_user_agent") != "") Util::log($this->envDS("env", "http_user_agent"), "HTTP_USER_AGENT");
		//TODO: Read GET/QUERY_STRING variables: ?_f=json
		//TODO: Set content type, into output datastore
		return $this->_outputFormat;
	}

	// Entire App should use the same language
	function suggestLanguage()
	{
		if (true) return $this->_lang;
		if (!$this->envDS("env", "http_accept_language") != "") return $this->_lang;
		$langs = explode(",", $this->envDS("env", "http_accept_language"));
		foreach ($langs as $lng) {
			// List all accepted languages and check, if one of them is available. Select the first match (and break the loop)
			// TODO: How the App knows what languages are present?!
			var_dump(substr($lng, 0, 2));
		}
		return $this->_lang;
	}

	function lbl($var = false, /*array*/ $translations = null)
	{
		if ($var === false) return isset($this->_lbl["_loaded"]); // Check if lbl() is usable

		if (is_array($var)) {
			$this->_lbl = $var;
			return $this;
		}
		if ($translations !== null) {
			$this->_lbl[$var] = $translations;
		}

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


	/*
	 * convert(obj); => Convert obj from type(obj) to defFormat
	 * convert(str, b); => Convert str from b to defFormat
	 * convert(obj, b); => Convert obj from type(obj) to defFormat.b
	 * convert(obj, b, c); => Convert obj from type(obj) to format b.c
	 * convert(str, b, c); => Convert str from type b to format c
	 * convert(str, b, c, d); => Convert str from type b to c.d
	*/
	function convert($data, $arg1 = null, $arg2 = null, $arg3 = null, $args = array())
	{
		$fromFormat = null;
		$toFormat = $this->_outputFormat;
		$toType = "";

		if ($data === null) throw new \Exception("Error: null is not convertible."); // TODO: Maybe debug only? May return empty string.

		// Data as String
		if (is_string($data)) {
			$data = trim($data);

			if ($arg2 === null) { // convert("str", p1)
				$fromFormat = $arg1;
			} elseif ($arg3 === null) { // convert("str", p1, p2)
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

			// Data as Object
		} elseif (is_object($data)) {
			$fromFormat = Util::getClassName($data);
			if ($arg2 === null) { // convert(obj, p1)
				$toType = $arg1;
			} elseif ($arg3 === null) { // convert(obj, p1, p2)
				$toFormat = $arg1;
				$toType = $arg2;
			} else {
				throw new \Exception("Error: Convert for ".$fromFormat." cannot define fromFormat, becuase it's derived from its type.");
			}
		}

		$convPath = "converters/".strToLower($fromFormat."_".$toFormat.($toType != "" ? "_".$toType : "")).".php";
		if (!file_exists((file_exists($this->pathApp().$convPath) ? $this->pathApp() : $this->path()).$convPath)) throw new \Exception("Error: Convertor ".$convPath." not found!"); // TODO: Disable for PROD for performance
		include_once ($this->pathApp() && file_exists($this->pathApp().$convPath) ? $this->pathApp() : $this->path()).$convPath;
		$convClass = "com\\qetrix\\converters\\".$fromFormat."_".$toFormat.($toType != "" ? "_".$toType : "");
		return (new $convClass($this))->convert($data, $args);
	}
}
