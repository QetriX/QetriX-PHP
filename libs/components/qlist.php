<?php declare(strict_types = 1);
namespace com\qetrix\libs\components;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.06.03 | QList Component PHP class
 */

use com\qetrix\libs\Util;

class QList extends Component
{
	protected $_cols = []; /// array of hash table col settings: name, dsname=name, heading=name, text=null, action=null
	protected $_sections = []; /// array of sections
	protected $_section = []; // section separator (column key, may not be QListCol)
	protected $_data = []; /// array of hash tables, representing whole dataset for the list, keys must match cols names
	protected $_orderBy = []; /// Column Names, e.g. ["name", "
	protected $_value = ""; /// Current selection
	protected $_valueKey = ""; /// Array key for value
	protected $_actionPathBase = "";

	private $_pageNum = 1; /// Current page
	private $_rowsPerPage = 20; /// Visible rows (per page)
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
	public function add($value, $position = null) // TODO: sections
	{
		// TODO: QListRow handling (is_object)
		if (is_array($value)) {
			//if ($position !== null) throw new \Exception("Position for add(array) not impolemented yet");
			if (count($value) > 0) $this->rows($value);

		} else {
			if (is_object($value) && get_class($value) == "com\\qetrix\\components\\qlist\\QListRow") $this->rows($value->toArray());
			else {
				if (!in_array("text", $this->_cols)) $this->addCol("text");
				if ($position === null) $this->_data[] = ["text" => $value];
				else array_unshift($this->_data, ["text" => $value]);
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
			$this->_data = array_merge($this->_data, $this->setValue($value));

			// Array of string, mostly some list or menu - array(string, string, string)
		} elseif (isset($value[0]) && isset($value[count($value) - 1])) {
			if ($this->_colsType == 0) {
				$this->addCol("text");
				$this->_colsType = 2;
			}
			foreach ($value as $v) $this->_data[] = ["text" => $v];

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
					if (isset($value["action"]) && $value["action"] == $this->value()) $value["selected"] = "1"; // I prefer missing key "selected" instead of setting negative value
				}
				if (is_array($value[key($value)])) $this->_data = array_merge($this->_data, $this->setValue($value));
				else {
					$this->_data[] = $this->setValue([$value])[0];
				}
			} else {
				if ($this->_colsType == 3) {
					$this->addCol("text");
					$this->_colsType = 4;
				} // Autogen cols
				foreach ($value as $k => $v) $this->_data[] = ["value" => $k, "text" => $v];
			}
		}

		//Util::log($value, $this->name()." (".$this->_colsType.")");

		if ($this->_colsType == 0) $this->_colsType = count($this->_cols);
		// TODO: If selected, set $_value
		return $this;
	}

	/** Remove one row from the List. Numeric = row index */
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
	public function addCol($name, $heading = null, $type = null, $action = null, $text = null /*, $dsid = null*/) // DSNAME!!!!!
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
		if ($type !== null) $col["type"] = $type;
		//if ($dsid !== null) $col["dsid"] = $dsid;
		//$col["dsid"] = isset($dsid) ? $dsid : $name;
		//if ($heading !== null) $this->_cols[$heading] = $action; else $this->_cols[] = $action; // -MNo 20150809: Table with defined cols not showing row values
		if ($heading !== null) $this->_cols[$heading] = $col; else $this->_cols[] = $col;
		return $this;
	}

	/*public function addCols($value)
	{
		if (!is_array($value)) throw new \Exception("Err#xy: Argument for QList.addCol(arg) must be an array, ".gettype($value)." given.");
		if (!isset($value["name"]) || $value["name"] == "") throw new \Exception("Err#xy: Argument for QList.addCol(arg) must contain \"name\" key with value.");
		$this->_colsType = 1;
		$this->_cols = array_merge($this->_cols, $value);
		return $this;
	}*/

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

	public function section($value = false, $text = false, $detail = false, $action = false)
	{
		if ($value === false) return $this->_section;
		// TODO: must be ordered by section!
		$this->_section = array("value" => $value);
		if ($text !== false) $this->_section["text"] = $text;
		if ($detail !== false) $this->_section["detail"] = $detail;
		if ($action !== false) $this->_section["action"] = $action;
		return $this;
	}

	public function sectionText($row = false)
	{
		return !isset($this->_section["text"]) ? $this->_section["value"] : ($row !== false ? Util::processVars($this->_section["text"], $row) : $this->_section["text"]);
	}

	// In HTML, if action contains slash ("/"), it's a link. Otherwise it's JS method
	/*public function action($value = null)
	{
		if ($value === null) return $this->_action; // Get data
		$this->_action = $value;
		return $this;
	}*/

	/** Sets or gets selected row
	 *
	 * @param $key = name (key) of value column ("value" is default)
	 * @param $value = sets value
	 *
	 * @return $this
	 */
	public function value($value = false, $key = "value")
	{
		if ($value === false) return $this->_value;
		$this->_value = $value;
		$this->_valueKey = $key;
		$this->_data = $this->setValue($this->_data);
		return $this;
	}

	/** Recursively traverses QList item tree and set "selected" where appropriate */
	private function setValue($data)
	{
		if ($this->_value == "") return $data;
		$value = $this->_value;
		$key = $this->_valueKey;
		for ($i = 0; $i < count($data); $i++) {
			if ($key != "action" && (isset($data[$i][$key]) && $data[$i][$key] == $value || (isset($data[$i]["text"]) && $data[$i]["text"] == $value))) {
				$data[$i]["selected"] = "1";
			} elseif (isset($data[$i]["action"])) {
				$this->_valueKey = "action";
				if ($data[$i]["action"] == $value) {
					unset($data[$i]["action"]); // Remove "action" to disable linking the page to itself
					$data[$i]["selected"] = "1";
				} else {
					$strrpos = strrpos($value, "/");
					if ($strrpos !== false && substr($value, 0, $strrpos) == $data[$i]["action"]) {
						$data[$i]["selected"] = "1";
					}
				}
			}
			if (isset($data[$i]["items"]) && is_array($data[$i]["items"])) {
				$data[$i]["items"] = $this->setValue($data[$i]["items"]);
			}
		}
		return $data;
	}

	public function actionBase($value = false)
	{
		if ($value === false) return $this->_actionPathBase;
		$this->_actionPathBase = $value;
		return $this;
	}
}
