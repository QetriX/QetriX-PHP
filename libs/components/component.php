<?php
namespace com\qetrix\libs\components;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 15.12.20 | QetriX Component PHP class
 */

use com\qetrix\libs\QApp;
use com\qetrix\libs\Util;

class Component
{
	/** @var \com\qetrix\libs\DataStore null */
	protected $datastore = null;
	/** @var String null */
	protected $_name = null;
	/** @var String null */
	protected $_heading = null;
	/** @var String null */
	protected $_action = null;

	protected $_style = null;
	protected $_app = null;

	/** PHP constructor */
	function __construct($name = null, $heading = null)
	{
		$this->_app = QApp::getInstance();
		$this->_name = $name;
		$this->_heading = $heading;
	}

	/**
	 * @return null
	 */
	public function name()
	{
		return $this->_name;
	}

	/**
	 * @param null $value
	 *
	 * @return $this|null
	 */
	public function heading($value = null)
	{
		if ($value === null) return $this->_heading;
		$this->_heading = $value;
		return $this;
	}

	/** Set a style (in HTML it's "class" attribute, not "style" attribute!) */
	public function style($value = null)
	{
		if ($value === null) return $this->_style;
		$this->_style = $value;
		return $this;
	}

	/** Get the app, used often by converters */
	public function app()
	{
		return $this->_app;
	}

	/** Convert the component to something else (HTML, JSON, XML...) */
	public function convert($arg1 = null, $arg2 = null, $arg3 = null)
	{
		return Util::convert($this, $arg1, $arg2, $arg3);
	}

	public function action($value = null)
	{
		if ($value === null) return $this->_action; // Get data
		$this->_action = $value;
		return $this;
	}
}
