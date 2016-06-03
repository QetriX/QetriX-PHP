<?php declare(strict_types = 1);
namespace com\qetrix\libs\components\qlist;

/* Copyright (c) QetriX. Licensed under MIT License, see /LICENSE.txt file.
 * 16.04.24 | QListCol part of QList component
 * Optional, replaceable by a hash table (associative array)
 */

class QListCol
{
	private $_action = "";
	private $_dsname = "";
	private $_heading = "";
	private $_items = [];
	private $_name = "";
	private $_precision = 1;
	private $_text = "";
	private $_type = "";

	/** Required, Unique column name (key), recommended [a-z][0-9] */
	function name($value = false)
	{
		if ($value === false) return $this->_name;
		$this->_name = $value;
		return $this;
	}

	/** Column name in DataStore (like table column, aka domain) */
	function dsname($value = false)
	{
		if ($value === false) return $this->_dsname;
		$this->_dsname = $value;
		return $this;
	}

	/** Table header text (if present in Converter) */
	function heading($value = false)
	{
		if ($value === false) return $this->_heading;
		$this->_heading = $value;
		return $this;
	}

	/** Custom cell content for that row, using Que with %variables% */
	function text($value = false)
	{
		if ($value === false) return $this->_text;
		$this->_text = $value;
		return $this;
	}

	/** Makes the cell clickable, overrides QList->action (if present) */
	function action($value = false)
	{
		if ($value === false) return $this->_action;
		$this->_action = $value;
		return $this;
	}

	/** Formats value of a cell (date, time, number...) */
	function type($value = false)
	{
		if ($value === false) return $this->_type;
		$this->_type = $value;
		return $this;
	}

	/** Enum for texts, when text is only a key (FK) */
	function items(array $value)
	{
		$this->_items = $value;
		return $this;
	}

	function item($key)
	{
		if (!isset($this->_items[$key])) return "";
		return $this->_items[$key];
	}

	function textPrecision($value = false)
	{
		if ($value === false) return $this->_precision;
		$this->_precision = $value;
		return $this;
	}

	function toArray()
	{
		$arr = array("name" => $this->name());
		if ($this->dsname() != "") $arr["dsname"] = $this->dsname();
		if ($this->heading() != "") $arr["heading"] = $this->heading();
		if ($this->action() != "") $arr["action"] = $this->action();
		if ($this->text() != "") $arr["text"] = $this->text();
		if ($this->type() != "") $arr["type"] = $this->type();
		if ($this->precision() != "") $arr["precision"] = $this->precision();
		//if ($this->_items != []) $arr["items"] = $this->items(); TODO
		return $arr;
	}
}

final class QListColType
{
	const date = "date";
	const datetime = "datetime";
	const image = "image";
	const number = "number";
	const text = "text";
	const time = "time";
}
