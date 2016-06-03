<?php //declare(strict_types = 1);
namespace com\qetrix\libs\components\qform;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.06.03 | QForm Control
 * Using "fluent interface", allows chaining
 */

use com\qetrix\libs\components\QForm;
use com\qetrix\libs\components\QList;
use com\qetrix\libs\QPage;
use com\qetrix\libs\Util;

class QFormControl
{
	protected $_action;
	protected $_detail;
	protected $_ds = ""; // tds
	protected $_dsname; // as 'col' (all form controls are in the same table) or 'table.col' (multiple tables)
	protected $_formName;
	protected $_items;
	protected $_label;
	protected $_max; // tvx
	protected $_min; // tvn
	protected $_mode; // tvm
	protected $_name; // tn
	protected $_oldValue; // prev pv
	protected $_order; // po
	protected $_parent;
	protected $_precision = 0; // 100 = rounded to nearest hundread, 0.01 = two decimal places
	protected $_property = "v"; // One control can hold only single property
	protected $_style; // HTML class
	protected $_text; // item text, placeholder
	protected $_type; // tvt, trt, tot: text, number, date, time, datetime, list
	protected $_unit; // tvu
	protected $_validation; // tvv
	protected $_value; // pv
	protected $_valueType;
	protected $style; // In HTML it's "class", not "style"!!!

	public function __construct($type, $name = false, $label = "", $value = "", $order = null, $items = null)
	{
		$this->QFormControl($type, $name, $label, $value, $order, $items);
	}

	/**
	 * @param $type
	 * @param string $name
	 * @param string $label
	 * @param string $value
	 * @param null $order
	 * @param null|array|string $items Array (List), associative array (HashMap), TSV string or QList of items. Menu items or autocomplete defaults.
	 *
	 * @throws \Exception
	 */
	public function QFormControl($type, $name = "", $label = "", $value = "", $order = null, $items = array()) // TODO: AutoName, allow name=null/false and generate it properly
	{
		/*if (is_numeric($type)) {
			$this->QType = new QType();
			$this->QType->ValueType($type);
		} elseif ($type instanceof QType) {
			$this->QType = $type;
		} else throw new \Exception("Invalid QType");*/
		if (is_string($type)) {
			$this->_type = strToLower($type);
			if ($type == QFormControlType::rel) $this->property("r"); //+MNo 20151013
		} else throw new \Exception("Invalid QFormControl Type");

		if ($name != "" && strpos($name, "-") !== false) throw new \Exception("Invalid QFormControl name: ".$name." (should be: ".str_replace("-", "", $name).")");
		$this->_name = $name; // "" = autonaming
		if (substr($name, -2) == "_r") {
			$this->_dsname = substr($name, 0, -2);
			$this->_property = "r";
		} elseif (substr($name, -2) == "_o") {
			$this->_dsname = substr($name, 0, -2);
			$this->_property = "o";
		} else $this->_dsname = $name;
		$this->_label = $label == "" && $this->_type != QFormControlType::button && $this->_type != QFormControlType::hidden ? $name : $label; // label = "" => name + lang
		$this->value($value);
		$this->_oldValue = $value;
		$this->_order = $order;
		if (count($items) > 0) $this->items($items); // Create list of items
		else $this->_items = $items;
	}

	public function label($value = false)
	{
		if ($value === false) return $this->_label;
		$value = trim($value);
		if ($value == "") throw new \Exception("Label can't be empty");
		$this->_label = $value;
		return $this;
	}

	public function name($value = false)
	{
		if ($value === false) return $this->_name;
		if (strpos($value, "-") !== false) throw new \Exception("Name (".$value.") can't contain dash, did you mean \"".str_replace($value, "-", "_")."\"?");
		$this->_name = strToLower($value);
		return $this;
	}

	/** Name of a form the control is assigned into */
	public function formName($value = false)
	{
		if ($value === false) return $this->_formName;
		$this->_formName = $value;
		return $this;
	}

	/**
	 * DataStore name, e.g. column in a table (string), typeID for new QPDB Particle (number), or particleID for existing QPDB Particle (number)
	 *
	 * @param bool $value
	 *
	 * @return $this
	 */
	public function dsname($value = false)
	{
		if ($value === false) return $this->_dsname;
		$this->_dsname = $value;
		return $this;
	}

	// DataStore.Scope (table)
	public function ds($value = false)
	{
		if ($value === false) return $this->_ds;
		if ($value == "") return $this; // TODO: ERROR

		if (strpos($value, ".") === false) $value = QPage::getInstance()->ds()->name().".".$value;
		$this->_ds = $value;
		return $this;
	}

	public function value($value = false)
	{
		if ($value === false) return $this->_value;
		if (is_array($value)) $value = implode("\t", $value);
		if ($this->_min !== null) {
			if ($this->_type == QFormControlType::text && mb_strLen($value) < $this->_min) throw new \Exception("Value must be at lest ".$this->_min." chars long");
			if ($this->_type == QFormControlType::number && $value > $this->_max) throw new \Exception("Value can't be smaller, than ".$this->_min."");
			// TODO: DATE, TIME...
		}

		if ($this->_max !== null) {
			if ($this->_type == QFormControlType::text && mb_strLen($value) > $this->_max) throw new \Exception("Value can't be longer, than ".$this->_max." chars");
			if ($this->_type == QFormControlType::number && $value > $this->_max) throw new \Exception("Value can't be bigger, than ".$this->_max."");
			// TODO: DATE, TIME...
		}
		if ($this->_precision !== null) {
			// TODO: Number
			// TODO: Date (weeks, months, years)
		}
		$this->_value = $value;
		return $this;
	}

	public function valueChanged()
	{
		return $this->_value != $this->_oldValue;
	}

/// For textbox it's a placeholder
	public function text($value = false)
	{
		if ($value === false) return $this->value() == "" ? "" : $this->_text; // No value = no text
		$this->_text = $value;
		return $this;
	}

/// For textbox it's help or value descriptoin
	public function detail($value = null)
	{
		if ($value === null) return $this->_detail;
		$this->_detail = $value;
		return $this;
	}

	public function style($value = false)
	{
		if ($value === false) return $this->_style;
		// TODO: check value for invalid characters (what can be in class?)
		$this->_style = $value;
		return $this;
	}

	public function order($value = false)
	{
		if ($value === false) return $this->_order;
		$this->_order = $value;
		return $this;
	}

/// For string value: min length
/// For numeric value: min value
/// For date and datetime: earliest date yyyymmdd
/// For time: min seconds
	public function valueMin($value = false)
	{
		if ($value === false) return $this->_min;
		$this->_min = $value;
		return $this;
	}

/// For string value: max length
/// For numeric value: max value
/// For date and datetime: furthest date yyyymmdd
/// For time: max seconds
	public function valueMax($value = false)
	{
		if ($value === false) return $this->_max;
		$this->_max = $value;
		return $this;
	}

/// For numeric value: 0.01 means two decimal places, 100 means round value to nearest hundread
/// For datetime value: see docs
	public function valuePrecision($value = false)
	{
		if ($value === false) return $this->_precision;
		if (!is_numeric($value)) throw new \Exception($this->name()."->valuePrecision: argument is not numeric!");
		$this->_precision = $value + 0;
		return $this;
	}

	public function valueType($value = false)
	{
		if ($value === false) return $this->_property == "v" ? $this->_type : $this->_valueType;
		$this->_valueType = $value;
		return $this;
	}

	public function valueUnit($value = false)
	{
		if ($value === false) return $this->_unit;
		$this->_unit = $value;
		return $this;
	}

	public function datastore()
	{
		return $this->_ds;
	}

	public function type()
	{
		return $this->_type;
		/*if ($this->QType === null) throw new \Exception("QType is null");
		elseif (!is_object($this->QType)) throw new \Exception("QType is not an object");
		return $this->QType->ValueType();*/
	}

	public function items($value = false)
	{
		if ($value === false) return $this->_items;
		if (!is_object($value)) $qlist = new QList($this->name()."_items"); else {
			$qlist = $value;
			$value = $qlist->rows();
		}
		if ($this->mode() != QFormControlMode::required) $qlist->add(array("value" => "", "text" => "-")); // Non-required fields allow to select "-" as null/nil
		$stringArrayKeyFix = isset($value[0]) && $value[0] !== null && isset($value[count($value) - 1]) && array_sum(array_keys($value)) == count($value) * (count($value) - 1) / 2 ? 1 : 0; // Zero-based index will start with 1, leaving 0 for null/nil
		foreach ($value as $val => $txt) {
			if (isset($txt["value"]) && isset($txt["text"])) $qlist->add($txt); // Items are in HashMap with "text" and "value" keys
			elseif (isset($txt["text"])) $qlist->add(array("value" => $txt["text"], "text" => $txt["text"]));
			elseif (isset($txt["value"])) $qlist->add(array("value" => $txt["value"], "text" => $txt["value"]));
			else $qlist->add(array("value" => $stringArrayKeyFix == 1 ? $txt : $val /*$val + $stringArrayKeyFix*/, "text" => $txt)); // Items are array of strings
		}
		$this->_items = $qlist;
		if ($this->_type != QFormControlType::multi) $this->_type = QFormControlType::menu;
		return $this;
	}

	/** Set or get control mode (QFormControlMode): Hidden, Disabled, ReadOnly, Normal, Required */
	public function mode($value = null)
	{
		// disabled, required
		if ($value === null) return $this->_mode;
		$this->_mode = $value;
		if ($value == QFormControlMode::required && count($this->_items) > 0) $this->_items->remove(0);
		return $this;
	}

	public function action($value = null)
	{
		if ($value === null) return $this->_action;
		$this->_action = $value;
		return $this;
	}

	/** Value validation */
	public function validation($value = null)
	{
		if ($value === null) return $this->_validation;
		$this->_validation = $value;
		return $this;
	}

	/** Parent control (defining lowest value) */
	public function parent($value = null)
	{
		if ($value === null) return $this->_parent;
		$this->_parent = $value;
		return $this;
	}

	/** Set property of assigned particle, changed by this control */
	public function property($value = null)
	{
		if ($value === null) return $this->_property;
		if ($value != "r" && $value != "v" && $value != "o") throw new \Exception("Property for QFormControl ".$this->name()." can be 'v', 'r' or 'o' only, ".$value." given.");

		if ($value == "r" && substr($this->name(), -2) != "_r") $this->_name .= "_r";
		elseif ($value == "o" && substr($this->name(), -2) != "_o") $this->_name .= "_o";

		$this->_property = $value;
		return $this;
	}
}

final class QFormControlType
{
	const button = "button";
	const checkbox = "checkbox";
	const datetime = "datetime";
	const email = "email";
	const file = "file";
	const hidden = "hidden";
	const htmltext = "htmltext";
	const longtext = "longtext";
	const menu = "list"; // "list" is a keyword :(
	const multi = "multi";
	const number = "number";
	const password = "password";
	const phone = "phone";
	const plain = "plain";
	const radio = "radio"; // radio group (eas radio as item), not a single radio
	const rel = "rel";
	const relation = "rel";
	const text = "text";
	const time = "time";
	const wikitext = "wikitext";
}

final class QFormControlMode
{
	const hidden = 0;
	const disabled = 1;
	const readonly = 2;
	const normal = 3;
	const required = 4;
}

final class QFormControlProperty
{
	const value = "v";
	const relation = "r";
	const order = "o";
}
