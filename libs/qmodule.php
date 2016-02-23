<?php namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.02.18 | QetriX Module PHP class
 */

use com\qetrix\libs\components\QList;
use com\qetrix\libs\QApp;
use com\qetrix\libs\QEntity;
use com\qetrix\libs\Util;

class QModule
{
	protected $ds; // Primary DataStore
	protected $varDS; // Variable files DataStore (logs, temps)
	protected $dataDS; // Data DataStore
	protected $contentDS; // Content DataStore (for PHP always FileSystem)

	public $stage;

	/** @var  QApp $app */
	protected $app;
	/** @var  QEntity $entity */
	protected $entity;
	protected $title = "QetriX";
	protected $user = null;

	// PHP constructor
	function __construct()
	{
		$this->QModule();
	}

	// Class Constructor
	function QModule()
	{
		$this->app = QApp::getInstance();
		$this->ds = $this->app()->ds();
		$this->stage = $this->app()->envDS()->get("env", "remote_addr") == "127.0.0.1" || $this->app->envDS()->get("env", "remote_addr") == "::1" ? QModuleStage::dev : QModuleStage::prod;
	}

	public function ds()
	{
		return $this->ds;
	}

	public function app()
	{
		return $this->app;
	}

	public function stage()
	{
		return $this->stage;
	}

	public function trl_ac($args)
	{
		$list = new QList("trl_ac");
		$list->add($this->ds()->trl_ac($this->app()->envDS("post", "value"), $args["id"]));
		return $list->convert();
	}
}

// QModuleStage enum
abstract class QModuleStage
{
	const debug = 1; // Debug env. on, detailed debug info, enabled only if something wents really wrong
	const dev = 2; // Debug env. on, debug info, stack trace, default for localhost/dev env
	const test = 3; // Testing, like production, but with testing (DS mockups for sending e-mails, WS push requests etc.), showing all warning or error messages
	const prod = 4; // Production, supressed warnings/error messages
}

/**
 * @property mixed _entID
 */
class QArgs
{
	private $_path;
	private $_id;

	public function path()
	{
		return $this->_path;
	}
	public function id()
	{
		return $this->_id;
	}
}
