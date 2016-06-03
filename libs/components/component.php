<?php declare(strict_types = 1);
namespace com\qetrix\libs\components;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.05.24 | QetriX Component PHP class
 */

use com\qetrix\libs\Dict;
use com\qetrix\libs\QModule;
use com\qetrix\libs\QPage;
use com\qetrix\libs\Util;
use com\qetrix\libs\ValueType;

class Component
{
	/** @var \com\qetrix\libs\DataStore null */
	protected $datastore = null;
	/** @var String null */
	protected $_name = "";
	/** @var String null */
	protected $_heading = "";
	/** @var String null */
	protected $_action = "";

	protected $_style = "";
	protected $_page = null;

	/** PHP constructor */
	function __construct($name = null, $heading = null)
	{
		$this->_page = QPage::getInstance();
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

	public function action($value = null, QModule $mod = null, Dict $args = null, $but = null)
	{
		if ($value === null) return $this->_action; // Get data
		$this->_action = $value;
		return $this;
	}

	/** Get the page, used often by converters
	 * @return QPage
	 */
	public function page()
	{
		return $this->_page;
	}

	/** Convert the component to something else (HTML, JSON, XML...) */
	public function convert($arg1 = null, $arg2 = null, $arg3 = null)
	{
		return Util::convert($this, $arg1, $arg2, $arg3);
	}

	public function formatValue($value, $type)
	{
		switch ($type+0) {
			case ValueType::dateTime:
				return Util::formatDateTime($value);
			case ValueType::number:
				return Util::formatNumber($value);
				//return date("", strToTime($value));
		}
		return $value;
	}
}
