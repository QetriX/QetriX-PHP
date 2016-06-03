<?php declare(strict_types = 1);
namespace com\qetrix\libs\components\qlist;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.06.02 | QListCol is part of QList component
 */

class QListRow
{
	protected $_data = []; // All data. Default keys: text, detail, action, value, image.
	protected $_items = null; // Child items; null = no childs, array() = possible childs (empty array)
	protected $_selected = false;

	function __construct($data, $text = "")
	{
		$this->QListRow($data, $text);
	}

	function QListRow($data, $text = "")
	{
		if (is_string($data)) $this->value($data);
		if ($text !== "") $this->text($text);
	}

	#region GETTERS AND SETTERS
	/** What will user see */
	function text($value = false) // left text
	{
		if ($value === false) return isset($this->_data["text"]) ? $this->_data["text"] : "";
		$this->_data["text"] = $value;
		return $this;
	}

	/** Value, like ID or code. May be hidden from the user */
	function value($value = false) // right text
	{
		if ($value === false) return isset($this->_data["value"]) ? $this->_data["value"] : "";
		$this->_data["value"] = $value;
		return $this;
	}

	/** Additional info, mostly by smaller font in a second row */
	function detail($value = false) // second row
	{
		if ($value === false) return isset($this->_data["detail"]) ? $this->_data["detail"] : "";
		$this->_data["detail"] = $value;
		return $this;
	}

	/** What will happend after user's interaction (tap, click...) with the row. Overrides QList->action() */
	function action($value = false) // chevron to the right. TODO: switch on/off
	{
		if ($value === false) return isset($this->_data["action"]) ? $this->_data["action"] : "";
		$this->_data["action"] = $value;
		return $this;
	}

	function actionType($value = false)
	{
		if ($value === false) return isset($this->_data["actiontype"]) ? $this->_data["actiontype"] : "";
		$this->_data["actiontype"] = $value;
		return $this;
	}

	/** Image (icon) on the left side of the row */
	function image($value = false) // left icon
	{
		if ($value === false) return isset($this->_data["image"]) ? $this->_data["image"] : "";
		$this->_data["image"] = $value;
		return $this;
	}

	/**
	 * @param bool $value
	 *
	 * @return $this|bool
	 * @throws \Exception
	 */
	function selected($value = false)
	{
		if ($value === false) return $this->_selected;
		if (!is_bool($value)) throw new \Exception("QListRow.selected accepts boolean only");
		$this->_selected = $value;
		return $this;
	}

	// Child items
	function items($value = false)
	{
		if ($value === false) return $this->_items;
		$this->_items = $value;
		return $this;
	}
	#endregion

	#region PUBLIC METHODS
	function toggleSelected()
	{
		$this->_selected = !$this->_selected;
		return $this->_selected;
	}
	#endregion

	function toArray()
	{
		return $this->_data;
	}
}
