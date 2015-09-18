<?php namespace com\qetrix\modules;

/* Copyright (c) 2015 QetriX. Licensed under MIT License, see /LICENSE.txt file.
 * QetriX Module PHP class
 */

use com\qetrix\libs\QApp;
use com\qetrix\libs\QEntity;
use com\qetrix\libs\Util;

class QModule
{
	public $ds; // Primary DataStore, TODO: will be protected, use ds() for reading instead
	public $dataDS; // Data DataStore
	public $contentDS; // Content DataStore (always filesystem)

	public $outputFormat;
	public $stage;

	/** @var  QApp $app */
	protected $app;
	/** @var  QEntity $entity */
	protected $entity;
	protected $title = "QetriX";
	protected $user = null;

	function __construct(QApp $app)
	{
		$this->QModule($app);
	}

	function QModule(QApp $app)
	{
		$this->app = $app;
		$this->ds = $this->app->ds();
		$this->stage = $this->app->envDS()->get("env", "remote_addr") == "127.0.0.1" || $this->app->envDS()->get("env", "remote_addr") == "::1" ? QModuleStage::Dev : QModuleStage::Prod;
		$this->outputFormat = $this->app->suggestOutputFormat();
	}

	public function ds()
	{
		return $this->ds;
	}

	public function app()
	{
		return $this->app;
	}

	/*
	 * Convert(obj); => Convert obj from type(obj) to defFormat
	 * Convert(str, b); => Convert str from b to defFormat
	 * Convert(obj, b); => Convert obj from type(obj) to defFormat.b
	 * Convert(obj, b, c); => Convert obj from type(obj) to format b.c
	 * Convert(str, b, c); => Convert str from type b to format c
	 * Convert(str, b, c, d); => Convert str from type b to c.d
	*/
	function convert($data, $arg1 = null, $arg2 = null, $arg3 = null)
	{
		$fromFormat = null;
		$toFormat = $this->outputFormat;
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
		if (!file_exists((file_exists($this->app->pathApp().$convPath) ? $this->app->pathApp() : $this->app->path()).$convPath)) throw new \Exception("Error: Convertor ".$convPath." not found!"); // TODO: Disable for PROD for performance
		include_once ($this->app->pathApp() && file_exists($this->app->pathApp().$convPath) ? $this->app->pathApp() : $this->app->path()).$convPath;
		$convClass = "com\\qetrix\\converters\\".$fromFormat."_".$toFormat.($toType != "" ? "_".$toType : "");
		return (new $convClass($this->app))->convert($data);
	}
}

// QModuleStage enum
abstract class QModuleStage
{
	const Debug = 1; // Debug env. on, detailed debug info, enabled only if something wents really wrong
	const Dev = 2; // Debug env. on, debug info, stack trace, default for localhost/dev env
	const Test = 3; // Testing, like production, but with testing (DS mockups for sending e-mails, WS push requests etc.), showing all warning or error messages
	const Prod = 4; // Production, supressed warnings/error messages
}
