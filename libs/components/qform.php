<?php
namespace com\qetrix\libs\components;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 2016-02-21 | QForm Component, for editing data in a single DS scope (e.g. DB table) or single QPDB Entity
 */

use com\qetrix\libs\components\qform\QFormControl;
use com\qetrix\libs\components\qform\QFormControlMode;
use com\qetrix\libs\components\qform\QFormControlProperty;
use com\qetrix\libs\components\qform\QFormControlType;
use com\qetrix\libs\Util;
use com\qetrix\libs\ValueType;

require_once __DIR__."/../qtype.php";
require_once __DIR__."/component.php";
require_once __DIR__."/qform/qformcontrol.php";

class QForm extends Component
{
	// protected $_action; // Form submit action
	protected $_controls = array(); // List of controls in the form
	protected $_target = null;
	protected $_data = array();
	protected $_rowsPerPart = 100; // Controls per section
	// protected $_langPrefix;
	protected $_path; // HTML attribute "action"

	protected $_entID = 0;
	protected $_classID = null;
	protected $_className = null;

	protected $nextControlOrder = 1;

	public function __construct($name, $heading = null, $content = null)
	{
		$this->QForm($name, $heading, $content);
	}

	public function QForm($name, $heading = null, $content = null)
	{
		parent::__construct($name, $heading);
	}

	// QForm Name
	public function name($value = false)
	{
		if ($value === false) return $this->_name;
		if ($value === "" || $value === null) throw new \Exception("Err#xy in QForm.name(value): Value can't be null or empty");
		$this->_name = $value;
		return $this;
	}

	/// Add new control - as QFormControl or array
	public function add($control)
	{
		// Single control
		if (is_object($control) && substr(get_class($control), -13) == "\\QFormControl") {
			/** @var $control QFormControl */
			foreach ($this->_controls as $c) if ($c->name() == $this->name()."-".$control->name()) throw new \Exception("Err#xy: Duplicate control name \"".$control->name()."\" in form \"".$this->name()."\".");
			if ($control->order() === null) $control->order($this->nextControlOrder); else $this->nextControlOrder = $control->order();
			$this->nextControlOrder++;
			if ($control->value() == "" && isset($this->_data[$control->name()])) $control->value($this->_data[$control->name()]); // Set a (new) value, if defined

			$control->name($this->name()."-".$control->name()); // Add QForm Name to Control's Name - disambiguation if more forms are processed at once
			$this->_controls[] = $control;

			// Array of controls or Control as array
		} elseif (is_array($control)) {
			if (!isset($control[0])) $control = array($control); // It's single control (as array)
			$this->addArray($control);
		}
	}

	/// Add new control(s), defined as array
	private function addArray($control)
	{
		$this->_rowsPerPart *= 10;

		$vals = array();
		foreach ($control as $cc) if (isset($cc["pv"])) $vals[$cc["name"]] = $cc["pv"];

		foreach ($control as $cc) {
			if (!isset($cc["order"])) $cc["order"] = $this->nextControlOrder; else $this->nextControlOrder = $cc["order"];
			$this->nextControlOrder++;

			//Util::log($cc);
			$propOrder = 0;
			foreach (array("v", "r", "o") as $prop) {
				if (isset($cc["t".$prop."t"]) && $cc["t".$prop."t"] > 0 && isset($cc["t".$prop."m"]) && $cc["t".$prop."m"] > 0) {
					if ($prop == "o" && $cc["tot"] < 5) continue; // Order by value asc/desc
					$label = $this->app()->lbl() ? $this->app()->lbl($cc["label"].($prop != "v" ? "_".$prop : "")) : $cc["label"];
					$c = new QFormControl(isset($cc["type"]) ? $cc["type"] : $this->xType($prop, $cc["t".$prop."t"]), $this->name()."-".$cc["name"]/*.($prop != "v" ? "_".$prop : "")*/, $label, $cc["p".$prop], ($cc["order"] * 3) + $propOrder);
					$c->dsname(isset($cc["dsname"]) ? $cc["dsname"] : $cc["name"]);
					$c->ds($cc["ds"]);
					$c->valueType($cc["t".$prop."t"]);
					if ($prop == "r") {
						$c->value($cc["rpp"]);
						$c->text($cc["rpv"]);

						if (isset($cc["trl"]) && $cc["trl"] > 0) {
							if ($cc["trt"] >= 30) {
								// TODO: use tds for values lookup
								switch ($cc["trt"]) {
									case 30: $c->items($this->app()->ds()->getRelationListClass($cc["trl"]));break;
								}
							} else {
								$c->action("trl_ac	".$cc["trl"]);
							}
						}

					} elseif ($prop == "v") {
						if ($cc["tvt"] == "2") $c->mode(QFormControlMode::readonly);
						if (isset($cc["tvp"]) && $cc["tvp"] != "") $c->valuePrecision($cc["tvp"]);

						if (isset($cc["tvv"]) && $cc["tvv"] != "" && substr($cc["tvv"], 0, 1) == "=") {
							$eq = Util::processVars(mb_substr($cc["tvv"], 1), $vals);
							$eqArr = explode(" ", str_replace(array("+"), array(" + "), $eq));
							$res = 0;
							$op = "+";
							for ($i=0; $i < count($eqArr); $i++) {
								if ($eqArr[$i] == "+" || $eqArr[$i] == "-" || $eqArr[$i] == "*" || $eqArr[$i] == "/") $op = $eqArr[$i];
								elseif (is_numeric($eqArr[$i]) && $op == "+") $res += $eqArr[$i]; // TODO TODO TODO!
							}
							$c->value($res);
						}
					}
					$c->property($prop);
					$this->_controls[] = $c;
					$propOrder++;
				}
			}
		}
	}

	private function xType($prop, $type)
	{
		switch ($prop) {
			case "v":
				switch ($type) {
					case 10: return "checkbox";
					case 12: return "number";
					case 16: return "datetime";
				}
				return "text";
			case "r":
				return "rel";
		}
		return "text";
	}

	/** Returns hashmap of changed values (key is dsname, value is new value). Keys are position in $form->controls array */
	public function changes($data, $validateValue = true)
	{
		if (count($this->controls()) == 0) throw new \Exception("setForm error: there are no controls in form ".$this->name());
		if (count($data) == 0) return null;

		$changes = array();
		$parts = array(); // Processed particles

		//Util::log($data, "qform.changes.data");
		foreach ($this->controls() as $cid => $control) {
			/** @var $control QFormControl */
			if ($control->type() == "button" || !isset($data[$control->name()]) || $control->mode() == QFormControlMode::disabled || $control->mode() == QFormControlMode::readonly) continue;
			if (!isset($data[$control->name()])) { // Control not found in $data, won't touch it
				//if (!$control->valueChanged()) continue;
				//$value = $control->value();
				continue; //+MNo 20160122, incl. two rows above and else below commented
			} // else
			$value = $data[$control->name()];
			if ($value == "") $value = null; // TODO: may cause problem with not-null db fields
			$name = $control->property() != "v" ? substr($control->name(), 0, -2) : $control->name();
			if (!isset($parts[$name]) || $parts[$name]["isnew"]) $parts[$name] = array("dsname" => $control->dsname(), "isnew" => $control->value() == "" ? "1" : "0");

			//Util::log("new:".$value.", old:".$control->value(), $name);

			/// Value changed or it's a static value in a new form
			if ($value != $control->value() || ($this->_entID == 0 && $value != "")) {
				//Util::log($control, $value);
				if (!isset($changes[$name])) {
					$ch = array("dsname" => $control->dsname());
					if ($value == "" && $control->value() != "") $ch["change"] = "-"; // Remove value/particle
					elseif ($value != "" && $control->value() == "") $ch["change"] = "+"; // Add value/particle
				} else {
					$ch = $changes[$name];
				}
				if ($parts[$name]["isnew"] == "0") $ch["change"] = "*";
				if ($this->_entID == 0) $ch["change"] = "+"; // In new form all values are new
				elseif ($control->dsname() == $this->_className && $this->_entID > 0) { // Control represents the Entity. TODO: Is this a right/the only way how to do it?
					$ch["change"] = "*";
					$ch["dsname"] = $this->_entID;
				}

				switch ($control->property()) {
					case "v":
					case QFormControlProperty::value:
						if ($control->type() == "checkbox" && $value == "") $value = "0";
						elseif ($validateValue) $value = $this->validateValue($value, $control);
						$ch["value"] = $value;
						break;
					case "r":
					case QFormControlProperty::relation:
						$ch["relation"] = $value;
						if ($control->type() == QFormControlType::rel) $control->text($data[$control->name()."_text"]);
						break;
					case "o":
					case QFormControlProperty::order:
						$ch["order"] = $value;
						break;
				}
				if ($control->ds() != "") $ch["ds"] = $control->ds(); // TODO! Custom tables, maybe form-defined ds (table, as className?)
				$changes[$name] = $ch;
				$control->value($value);
			}
			if ($control->mode() == QFormControlMode::required && $control->value() == "") throw new \Exception($control->label()." has required value.");
		}
		//Util::log($parts, "parts");
		//Util::log($changes, "changes: ".$this->name());
		return $changes;
	}

	private function validateValue($value, QFormControl $control)
	{
		switch ($control->valueType()) {
			case "num":
			case "number":
			case ValueType::number:
				if ($control->valuePrecision() > 0 && $control->valuePrecision() < 1) return $this->correctDec($value);
				return $this->correctNum($value);
			case "datetime":
			case ValueType::dateTime:
				$date = $this->correctDate($value);
				switch ($control->valuePrecision()) {
					case 1: return substr($date, 0, 4); // Year
					case 6: return substr($date, 0, 8); // Day
					case 8: return substr($date, 0, 12); // Minute
					case 9: return substr($date, 0, 14); // Second
				}
				return $date;
		}
		return trim($value);
	}

	// How many controls should be in a form's part (for rendering the form in Converter)
	public function rpp($value = false)
	{
		return $this->_rowsPerPart;
	}

	// Array of controls in the form
	public function controls()
	{
		return $this->_controls;
	}

	// How many controls are in the form
	public function size() // length()? count()?
	{
		return count($this->_controls);
	}

	// Get single control
	public function control($index)
	{
		return $this->_controls[$index];
	}

	// createFromQType(typeID)
	// createFromEntity(entID)
	// createFromParticle(pID)
	// UPDATE subjekty SET index_adresy = (select TRIM(upper(ulice) || ' ' || cislo_domovni || ' ' || psc || ' ' || upper(obec)) as txt from v_adresy where subjekt_id = subjekt_pk and rownum = 1) where index_adresy is null AND subjekt_pk = subjekt_id;

	public function path($value = false)
	{
		if ($value === false) return $this->_path;
		$this->_path = $value;
		return $this;
	}

	/*public function langPrefix($value)
	{
		$this->_langPrefix = $value;
		return $this;
	}*/

	public function target($value = false)
	{
		if ($value === false) return $this->_target;
		$this->_target = $value;
		return $this;
	}

	public function entID($value = false)
	{
		if ($value === false) return $this->_entID;
		$this->_entID = $value;
		return $this;
	}

	public function classID($value = false)
	{
		if ($value === false) return $this->_classID;
		$this->_classID = $value;
		return $this;
	}

	public function className($value = false)
	{
		if ($value === false) return $this->_className;
		$this->_className = $value;
		return $this;
	}

	public function data($value)
	{
		if (!is_array($value)) throw new \Exception("Err#xy: QForm(".$this->name().").data argument has to be an array, ".gettype($value)." given."); // TODO: cleanup
		if (is_array($value) && count($value) == 1 && isset($value[0]) && is_array($value[0])) $value = $value[0];

		$this->_data = array_merge($this->_data, $value);

		/// If controls exists, fill'em with data
		if (count($this->_controls) > 0) foreach ($this->_controls as $c) {
			//Util::log($c->name()." / ".$c->dsname());
			if (isset($value[$c->dsname()])) $c->value($value[$c->dsname()]);
			elseif (isset($value[$c->name()])) $c->value($value[$c->name()]);
			elseif (isset($value[$c->dsname()."_rv"])) $c->text($value[$c->dsname()."_rv"]);
			elseif (isset($value[$c->name()."_rv"])) $c->text($value[$c->name()."_rv"]);
		}
	}

	public function getValue($name)
	{
		return isset($this->_data[$name]) ? $this->_data[$name] : null;
	}

	public function action($value = null)
	{
		if ($value === null) return $this->_action;
		$this->_action = $value;
		return $this;
	}


	/**
	 * IBAN verification, luhn is common verification algorithm - see Wikipedia
	 *
	 * @param $number
	 *
	 * @return bool
	 */
	function isValidLuhn($number)
	{
		settype($number, "string");
		$sumTable = array(array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9), array(0, 2, 4, 6, 8, 1, 3, 5, 7, 9));
		$sum = 0;
		$flip = 0;
		for ($i = strlen($number) - 1; $i >= 0; $i--) $sum += $sumTable[$flip++ & 0x1][$number[$i]];
		return $sum % 10 === 0;
	}

//region correctNum
	function correctNum($num, $onlyPositive = false)
	{
		$num = trim($num);
		$num = $this->replaceCharsWithNums($num);
		$num = $this->correctDotsZeros($num);
		if ($onlyPositive) $num = preg_replace("/[^0-9]/", "", $num); else $num = preg_replace("/[^0-9\-]/", "", $num);
		return is_numeric($num) ? $num : "	";
	}

	function correctDec($num, $onlyPositive = false)
	{
		$num = trim($num);
		$num = $this->replaceCharsWithNums($num);

		if (strpos($num, ",") !== false)
			if (strrpos($num, ",") > strlen($num) - 4 || $num < 1) $num = str_replace(",", ".", $num);
			else $num = substr($num, 0, strrpos($num, ",")).substr($num, strrpos($num, ",") + 1); /// 1,756 in EN is 1756, but 1,75 or 1,7 in CZ is 1.54
		$num = $this->correctDotsZeros($num);
		if (strpos($num, ".") !== false) {
			if (substr($num, -1) === "0") $num = substr($num, 0, -1); /// Removes ending zero
			if (substr($num, -1) === "0") $num = substr($num, 0, -1); /// Removes ending zero
			if (substr($num, -1) === "0") $num = substr($num, 0, -1); /// Removes ending zero
		}
		// \s
		if ($onlyPositive) $num = preg_replace("/[^0-9\.]/", "", $num); else $num = preg_replace("/[^0-9\-\.]/", "", $num);
		return is_numeric($num) ? $num : "	";
	}

	function replaceCharsWithNums($str)
	{
		return str_replace(array(" ", "O", "I", "l", "S", "A", "B", "Z"),
			array("", "0", "1", "1", "5", "4", "8", "2"),
			$str);
	}

	function correctDotsZeros($num)
	{
		$numx = str_replace(",", ".", $num);
		if (substr($numx, -3) === ".00") $num = substr($numx, 0, -3); /// Removes ending zeros (e.g. "1234.00") - it's an integer
		if (substr($numx, -2) === ".0") $num = substr($numx, 0, -2);  /// Removes ending zeros (e.g. "1234.0") - it's an integer
		if (substr($numx, -1) === ".") $num = substr($numx, 0, -1);   /// Removes ending dot (e.g. "1234.") - it's not a decimal number
		return $num;
	}

//endregion

//region correctDate
	/** Converts different date formats to QetriX Date format (yyyymmddhh24mi)
	 *
	 * @param $date
	 * @param bool $time
	 *
	 * @return string
	 */
	function correctDate($date, $time = false)
	{
		if (substr($date, -1) === ".") $date = substr($date, 0, -1); /// Removes ending dot
		$date = trim($date);
		if ($date == "") return "";
		if (is_numeric($date)) return substr($date."00000000000000", 0, 14); elseif (strToTime($date) > 0) return substr(date("YmdHis", strToTime($date))."00000000000000", 0, 14);

		if (strpos($date, ".") > 0) { // Expected: (d)d.(m)m.yyyy
			$date = str_replace(". ", ".", $date);
			$date = str_replace(" ", ".", $date);
			$arr = explode(".", $date);
			$arr[1] = $this->getMonth($arr[1]);
			$date = $arr[2].substr("00".$arr[1], -2).substr("00".$arr[0], -2);
			if (isset($arr[3])) $date .= str_replace(":", "", $arr[3]);

		} elseif (strpos($date, "-") > 0) { // Expected: yyyy-mm-dd
			$arr = explode("-", $date);
			if ($arr[0] > 31) $date = $arr[0].substr("0".$arr[1], -2).substr("0".$arr[2], -2);
			else $date = $arr[2].substr("0".$arr[1], -2).substr("0".$arr[0], -2);

		} elseif (strpos($date, "/") > 0) {
			$date = str_replace(" /", "/", $date);
			$date = str_replace("/ ", "/", $date);
			if (str_replace("/", "", $date) == substr($date, 0, 2).substr($date, -4)) { // Expected: mm/yyyy
				$date = substr($date, -4).substr($date, 0, 2);
			} else { // Expected: mm/dd/yyyy
				$arr = explode("/", $date);
				$date = $arr[2].substr("0".$arr[0], -2).substr("0".$arr[1], -2);
			}

		} elseif (strpos($date, ",") > 0) { // Expected: mmm dd, yyyy
			$date = str_replace(",", " ", $date);
			$date = str_replace("  ", " ", $date);
			$arr = explode(" ", $date);
			$arr[0] = $this->getMonth($arr[0]);
			$date = $arr[2].substr("0".$arr[0], -2).substr("0".$arr[1], -2);

		} elseif (substr_count($date, " ") == 2) {
			$arr = explode(" ", $date);
			$arr[1] = $this->getMonth($arr[1]);
			$date = $arr[2].substr("0".$arr[1], -2).substr("0".$arr[0], -2);
		}
		if (substr($date, 4, 2) > 12) $date = substr($date, 0, 4).substr($date, 6, 2).substr($date, 4, 2);

		// TODO: Check if all datetime components are inside boundaries (like hour is 00-23, second 00-60 (leap) etc.)

		return substr($this->correctNum($date)."00000000000000", 0, 14);
	}

	function getMonth($monthName)
	{
		if (is_numeric($monthName)) return $monthName;
		switch (str_replace(array("ě", "š", "č", "ř", "ž", "ý", "á", "í", "é", "ú", "ů"), array("e", "s", "c", "r", "z", "y", "a", "i", "e", "u", "u"), mb_strToLower(mb_substr($monthName, 0, 3)))) {
			case "jan":
			case "led":
				return "1";
			case "feb":
			case "uno":
				return "2";
			case "mar":
			case "bre":
				return "3";
			case "apr":
			case "dub":
				return "4";
			case "may":
			case "kve":
				return "5";
			case "jun":
				return "6";
			case "cer":
				if (substr($monthName, -1) == "c" || substr($monthName, -2) == "ce") return "7"; else return "6";
			case "jul":
			case "cnc":
				return "7";
			case "aug":
			case "srp":
				return "8";
			case "sep":
			case "zar":
				return "9";
			case "oct":
			case "rij":
				return "10";
			case "nov":
			case "lis":
				return "11";
			case "dec":
			case "pro":
				return "12";
		}
		return "0";
	}

	public function setItems($name, $items)
	{
		for ($i = 0; $i < count($this->_controls); $i++) if ($this->_controls[$i]->name() == $name) {
			$this->_controls[$i]->items($items);
			return $this;
		}
		throw new \Exception("Err#xy: Control ".$name." not found.");
	}
//endregion
}