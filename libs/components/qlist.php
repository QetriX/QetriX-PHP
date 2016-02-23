<?php namespace com\qetrix\libs\components;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 2016-01-08 | QList Component PHP class
 */

use com\qetrix\libs\components\qlist\QListCol;
use com\qetrix\libs\Util;

require_once dirname(__FILE__)."/component.php";
require_once dirname(__FILE__)."/qlist/qlistcol.php";

class QList extends Component
{
	protected $_cols = array(); /// array of hash table col settings: name, dsname=name, heading=name, text=null, action=null
	protected $_data = array(); /// array of hash tables, representing whole dataset for the list, keys must match cols names
	protected $_orderBy = array(); /// Column Names, e.g. ["name", "
	//protected $_action; /// Selected row change
	protected $_value; /// Current selection
	protected $_linkBase = "";

	private $_page = 1; /// Current page
	private $_rows = 20; /// Visible rows (per page)
	private $_colsType = 0; // For cols autogen

	/** PHP constructor */
	function __construct($name = null, $heading = null)
	{
		$this->QList($name, $heading);
	}

	/** Class constructor */
	function QList($name = null, $heading = null)
	{
		parent::__construct();
		$this->_name = $name;
		$this->_heading = $heading;
	}

	/** Items, entries
	 * String $value => "text"=>$value
	 * List<String> $value => ["text"=>$val1],["text"=>$val2],[..]
	 * HashMap<String, String> $value =>
	 */
	public function add($value, $position = null)
	{
		// TODO: QListRow handling (is_object)
		if (is_array($value)) {
			//if ($position !== null) throw new \Exception("Position for add(array) not impolemented yet");
			$this->rows($value);

		} else {
			if (is_object($value) && get_class($value) == "com\\qetrix\\components\\qlist\\QListRow") $this->rows($value->toArray());
			else {
				if (!in_array("text", $this->_cols)) $this->addCol("text");
				if ($position === null) $this->_data[] = array("text" => $value);
				else array_unshift($this->_data, array("text" => $value));
			}
		}

		return $this;
	}

	/** Add rows to the List */
	public function rows($value = null)
	{
		if ($value === null) return $this->_data; // Return rows

		/* colsTypes:
		 * 0 = default, unknown
		 * 1 = array of arrays
		 * 2 = array of strings
		 * 3 = zero numeric key cols
		 * 4 = some numeric key cols
		 * 5 = zero string key cols
		 * 6 = some string key cols
		 */

		// Array of arrays, mostly output from a DataStore - array(array(...), array(...), array(...))
		if (isset($value[0]) && is_array($value[0])) {
			if ($this->_colsType == 0) {
				foreach ($value[0] as $k => $v) $this->addCol($k);
				$this->_colsType = 1;
			} // Create cols from the first row (gullible)
			$this->_data = array_merge($this->_data, $value);

			// Array of string, mostly some list or menu - array(string, string, string)
		} elseif (isset($value[0]) && isset($value[count($value) - 1])) {
			if ($this->_colsType == 0) {
				$this->addCol("text");
				$this->_colsType = 2;
			}
			foreach ($value as $v) $this->_data[] = array("text" => $v);

		} else {
			if ($this->_colsType == 0) {
				$this->_colsType = count($this->_cols) > 0 ? 4 : 3;
				foreach ($value as $a => $b) if (!is_numeric($a)) {
					$this->_colsType = count($this->_cols) > 0 ? 6 : 5;
					break;
				}
			}
			if ($this->_colsType > 4 || $this->_colsType == 1) { // Table, or manually-added cols (colsType = 1)
				if ($this->_colsType == 5) { // Autogen cols
					foreach ($value as $k => $v) $this->addCol($k);
					$this->_colsType = 6;
				}
				if ($this->value() != "") { // QList->value has been defined, check if currently processed item fits
					if (isset($value["action"]) && $value["action"] == $this->value()) $value["selected"] = true; // I prefer missing key "selected" instead of setting it to false
				}
				$this->_data[] = $value;
			} else {
				if ($this->_colsType == 3) {
					$this->addCol("text");
					$this->_colsType = 4;
				} // Autogen cols
				foreach ($value as $k => $v) $this->_data[] = array("value" => $k, "text" => $v);
			}
		}

		//Util::log($value, $this->name()." (".$this->_colsType.")");

		if ($this->_colsType == 0) $this->_colsType = count($this->_cols);
		// TODO: If selected, set $_value
		return $this;
	}

	/** Remove one row from the List */
	public function remove($what)
	{
		if (is_numeric($what)) { // Remove by position
			unset($this->_data[$what]);
			$this->_data = array_values($this->_data); // Reindex the array
		} else { // Remove by what?

		}
		return $this;
	}

	/** How many rows (items, entries) are in the list */
	public function length()
	{
		return count($this->_data);
	}

	/** Add new col */
	public function addCol($name, $heading = null, $action = null, $text = null, $dsid = null)
	{
		$col = [];
		$col["name"] = $name;
		$this->_colsType = 1;
		if ($heading === null && $action === null) {
			$this->_cols[] = $col;
			return $this;
		}
		if ($heading !== null) $col["heading"] = $heading;
		if ($action !== null) $col["action"] = $action;
		if ($text !== null) $col["text"] = $text;
		if ($dsid !== null) $col["dsid"] = $dsid;
		//$col["dsid"] = isset($dsid) ? $dsid : $name;
		//if ($heading !== null) $this->_cols[$heading] = $action; else $this->_cols[] = $action; // -MNo 20150809: Table with defined cols not showing row values
		if ($heading !== null) $this->_cols[$heading] = $col; else $this->_cols[] = $col;
		return $this;
	}

	public function addCols($value)
	{
		if (!is_array($value)) throw new \Exception("Err#xy: Argument for QList.addCol(arg) must be an array, ".gettype($value)." given.");
		if (!isset($value["name"]) || $value["name"] == "") throw new \Exception("Err#xy: Argument for QList.addCol(arg) must contain \"name\" key with value.");
		$this->_colsType = 1;
		$this->_cols = array_merge($this->_cols, $value);
		return $this;
	}

	/** Data types, columns */
	public function cols($value = null)
	{
		if ($value === null) return $this->_cols; // Get data
		if (!is_array($value)) throw new \Exception("Err#xy: Argument for QList.cols(arg) must be an array, ".gettype($value)." given.");
		$this->_colsType = 1;
		$this->_cols = $value;
		return $this;
	}

	public function name()
	{
		return $this->_name;
	}

	// In HTML, if action contains slash ("/"), it's a link. Otherwise it's JS method
	/*public function action($value = null)
	{
		if ($value === null) return $this->_action; // Get data
		$this->_action = $value;
		return $this;
	}*/

	/** Sets or gets selected row
	 * value = sets value
	 * name = name of value column ("value" is default)
	 */
	public function value($value = false, $name = "value")
	{
		if ($value === false) return $this->_value;
		$this->_value = $value;
		for ($i = 0; $i < count($this->_data); $i++) {
			if ((isset($this->_data[$i][$name]) ? $this->_data[$i][$name] : $this->_data[$i]["text"]) == $value) {
				$this->_data[$i]["selected"] = true;
				break;
			} elseif (isset($this->_data[$i]["link"]) && mb_substr($this->_data[$i]["link"], 0, mb_strlen($value)) == $value) {
				if ($this->_data[$i]["link"] == $value) unset($this->_data[$i]["link"]);
				else $this->_data[$i]["selected"] = true;
				break;
			}
		}
		return $this;
	}

	public function linkBase($value = false)
	{
		if ($value === false) return $this->_linkBase;
		$this->_linkBase = $value;
		return $this;
	}
}