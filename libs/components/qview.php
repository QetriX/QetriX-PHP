<?php
namespace com\qetrix\libs\components;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.02.18 | QView Component
 */

use com\qetrix\libs\Util;

require_once dirname(__FILE__)."/component.php";

class QView extends Component
{
	private $_sections = array();
	private $ent = null;

	/** PHP Constructor */
	function __construct($name = null, $heading = null)
	{
		$this->QView($name, $heading);
	}

	/** Class Constructor */
	function QView($name = null, $heading = null)
	{
		parent::__construct();
		$this->_name = $name;
		$this->_heading = $heading;
	}

	/** Add a section (string to direct output) */
	function add($value, $scope = "page")
	{
		if (!isset($this->_sections[$scope])) $this->_sections[$scope] = array();

		if (is_string($value)) {
			$this->_sections[$scope][] = $value;

		} elseif (is_object($value)) {
			$ent = $value;
			//Util::log($value, get_class($value));
		}

		return $this;
	}

	function data($value)
	{
	}

	/** Get sections (used by Convert) */
	function sections($scope = "page")
	{
		if (isset($this->_sections[$scope])) return $this->_sections[$scope];
		return null;
	}
}
