<?php declare(strict_types = 1);
namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 17.06.19 | QetriX Data Element, combines Particle and Type
 */

final class QElem
{
	// Type properties
	protected $_id = ""; /// Primary Key, null = new (not in DS yet) => dsname (t_pk)
	protected $_ds; /// DataStore: engine.table (tds)
	protected $_name; /// Lang key for name (tn)
	protected $_desc = ""; /// Description / detail
	protected $_parent = ""; /// Parent type => parent dsname
	protected $tmo = 1; /// Max occurences in class. -1 = is ent, 0 = disabled, 1 = single, 2 = multiple (complex)
	protected $_order = 0; /// Order in class (toc)
	protected $_valueType = ElemType::text; // Value type (tvt)
	protected $_valueUnit = ""; /// Value unit (SI, if possible) (tvu)
	/** @var ElemMode */
	protected $_valueMode = ElemMode::normal; // Value mode (R/O, req, unique...) (tvm)
	protected $_valueMin = null; /// Float, Min value (num) / min length (str) (tvn)
	protected $_valueMax = null; /// Float, Max value (num) / max length (str) (tvx)
	protected $_valuePrecision = null; /// Float, Value precision (tvp)
	protected $_valueValidation = ""; /// Value validation (tvv)
	protected $_valueDefault = ""; /// Default value (tvd)
	//protected $trt = RelationType::none; // Relation type
	//protected $trm = RelationMode::hidden; // Relation mode
	protected $_relationList = 0; // Relation list (domain - Particle ID or Class ID) (trl)
	//protected $tot = OrderType::none; // Order type
	protected $_tag = ""; // QetriX Tag for specific purposes (marked as parent, coords, user...) (tg)

	// Value properties
	protected $_action = "";
	protected $_compname = ""; // Parent component name
	protected $_items = []; // List<QItem>
	protected $_label = ""; // Control label or column heading
	protected $_style = "";
	protected $_text = "";
	protected $_values = []; // Current value

	/** Delm - Control in QForm or column in QList
	 *
	 * @param mixed $data Array of Delm data OR numeric ValueType/RelationType/OrderType OR string name of Det
	 * @param string $name Name
	 * @param string $label Label
	 * @param string $value Value
	 * @param int $order Order - higher number = lower in QForm or further right in QList
	 * @param array $items Array of QItems
	 *
	 * @internal param $mix
	 */
	function __construct($data = null, $name = "", $label = "", $value = "", $order = null, array $items = [])
	{
		return $this->QElem($data, $name, $label, $value, $order, $items);
	}

	/**
	 * @param null $data
	 * @param string $name
	 * @param string $label
	 * @param string $value Default value; might be replaced by QForm->data([...]), if present
	 * @param int $order
	 * @param array $items
	 *
	 * @throws \Exception
	 * @return $this
	 */
	public function QElem($data = null, $name = "", $label = "", $value = "", $order = null, array $items = [])
	{
		if ($data === null) return $this;
		if (is_array($data)) foreach ($data as $k => $v) {
			$prop = (property_exists($this, $k) ? "" : "_").$k;
			if ($prop == $k || property_exists($this, $prop)) $this->$prop = $v;
			if (isset($data["value"])) $this->_values = [$data["value"]];
		} elseif (is_numeric($data)) {
			if ($name != "" && strpos($name."", "-") !== false) throw new \Exception("Invalid QElem name: ".$name." (should be: ".str_replace("-", "", $name).")");
			$this->_name = $name.""; // "" = autonaming
			$this->_id = $name.""; // dsname
			$this->_valueType = $data;
			if (($data > 99 && $data < 200) || substr($name."", -2) == "_r") {
				$this->_ds = substr($name, 0, -2);
				$this->value($value);
				$this->text($value);
			} elseif ($data > 199 || substr($name."", -2) == "_o") {
				$this->_ds = substr($name, 0, -2);
				$this->value($value);
			} else {
				$this->_ds = $name."";
				$this->value($value);
			}
			$this->_label = $label == "" && $this->_valueType != ElemType::result ? $name."" : $label; // label = "" => name + lang
			$this->_valueDefault = $value; // Set default value
			$this->_order = $order == "" ? null : $order;
			if (count($items) > 0) $this->items($items); // Create list of items
		} elseif (is_string($data)) {
			$this->_name = $data;
		}
		return $this;
	}

	/** Element name in DataStore (database table column, JSON object key). Used in backend.
	 *
	 * @param bool $value
	 *
	 * @return string|QElem
	 */
	public function dsname($value = false)
	{
		if ($value === false) return $this->_id;
		$this->_id = $value;
		return $this;
	}

	public function ds($value = false)
	{
		if ($value === false) return $this->_ds;
		$this->_ds = $value;
		return $this;
	}

	/** Element name in HTML. Used in frontend.
	 *
	 * @param null $value
	 *
	 * @return string|QElem
	 */
	public function name($value = null)
	{
		if ($value === null) return $this->_name;
		$this->_name = $value."";
		return $this;
	}

	/** Name of the component, to which the QElem belongs
	 *
	 * @param bool|string $value
	 *
	 * @return QElem|string
	 */
	public function compName($value = false)
	{
		if ($value === false) return $this->_compname;
		$this->_compname = $value;
		return $this;
	}

	/** Parent control (defining lowest value or filter for multi-level enums or ID of enum - dual Maximo style)
	 *
	 * @param bool $value
	 *
	 * @return string|QElem
	 */
	public function parent($value = false)
	{
		if ($value === null) return $this->_parent;
		$this->_parent = $value;
		return $this;
	}

	/** Set a value; won't be replaced by QForm->data([...])
	 *
	 * @param string|bool $value
	 * @param int $index
	 *
	 * @return QElem|string
	 * @throws \Exception
	 */
	public function value($value = null, $index = 0)
	{
		if ($value === null) return isset($this->_values[$index]) ? $this->_values[$index] : "";

		if (is_array($value)) $value = json_encode($value);
		elseif (is_string($value)) $value = trim($value);

		if (strlen($value."") > 0) {
			if ($this->_valueMin !== null) {
				if ($this->type() == ElemType::text && mb_strLen($value) < $this->_valueMin) throw new \Exception("Value (".$value.") must be at lest ".$this->_valueMin." chars long");
				if ($this->type() == ElemType::number && $value < $this->_valueMin) throw new \Exception("Value (".$value.") can't be smaller, than ".$this->_valueMin."");
				// TODO: DATE, TIME...
			}

			if ($this->_valueMax !== null) {
				if ($this->type() == ElemType::text && mb_strLen($value) > $this->_valueMax) throw new \Exception("Value (".$value.") can't be longer, than ".$this->_valueMax." chars");
				if ($this->type() == ElemType::number && $value > $this->_valueMax) throw new \Exception("Value (".$value.") can't be greater, than ".$this->_valueMax."");
				// TODO: DATE, TIME...
			}
			if ($this->_valuePrecision !== null) {
				// TODO: Number
				// TODO: Date (weeks, months, years)
			}

			// TODO: Check parent's value?
		}
		$this->_values[$index] = $value;
		return $this;
	}

	/** Set or get array of values
	 *
	 * @param null $values
	 *
	 * @return QElem|array
	 */
	public function values($values = null)
	{
		if ($values === null) return $this->_values;
		for ($i = 0; $i < count($values); $i++) $this->value($values[$i], $i);
		return $this;
	}

	public function defaultValue()
	{
		return $this->_valueDefault;
	}

	/**
	 * @param null $value
	 *
	 * @return QElem|string
	 */
	public function type($value = null)
	{
		if ($value === null) return $this->_valueType;
		$this->_valueType = $value;
		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return QElem|ElemMode
	 */
	public function mode($value = null)
	{
		if ($value === null) return $this->_valueMode;
		$this->_valueMode = $value;
		return $this;
	}

	/// For string value: min length
	/// For numeric value: min value
	/// For date and datetime: earliest date yyyymmdd (incl. entered date)
	/// For time: min seconds
	public function min($value = null)
	{
		if ($value === null) return $this->_valueMin;
		$this->_valueMin = $value;
		return $this;
	}

	/// For string value: max length
	/// For numeric value: max value
	/// For date and datetime: furthest date yyyymmdd (incl. entered date)
	/// For time: max seconds
	public function max($value = null)
	{
		if ($value === null) return $this->_valueMax;
		$this->_valueMax = $value;
		return $this;
	}

	/** Value precision
	 * Number: 100=hundreads, 10=tens, 0.1=1/10, 0.01=1/100
	 * DateTime: 1=Century, 2=Decade, 3=Year, 4=Quarter, 5=Month, 6=Week, 7=Day, 8=Hour, 9=Minute, 10=Second
	 * Time (duration): In seconds; 86400 = day, 3600 = hour, 300 = 5 mins, 60 = min, 0.01 = 1/100 sec
	 */
	public function precision($value = null)
	{
		if ($value === null) return $this->_valuePrecision;
		if ($value == 0) $value = null;
		$this->_valuePrecision = $value;
		return $this;
	}

	/** Value validation, list or regexp */
	public function validation($value = null)
	{
		if ($value === null) return $this->_valueValidation;
		$this->_valueValidation = $value;
		return $this;
	}

	public function validate()
	{
		throw new \Exception("Validation not yet implemented");
	}

	/** For textbox it's a placeholder
	 *
	 * @param null $value
	 *
	 * @return string|QElem
	 */
	public function text($value = null)
	{
		//if ($value === null) return $this->value() == "" ? "" : $this->_text; // No value = no text
		if ($value === null) return $this->_text; // Because $tableColDet->text() should return text
		$this->_text = $value;
		return $this;
	}

	/** xxx
	 *
	 * @param null $value
	 *
	 * @return QElem|string
	 */
	public function unit($value = null)
	{
		if ($value === null) return $this->_valueUnit;
		$this->_valueUnit = $value;
		return $this;
	}

	/** For textbox it's help or value descriptoin
	 *
	 * @param string $value
	 *
	 * @return QElem|string
	 */
	public function detail($value = null)
	{
		if ($value === null) return $this->_desc;
		$this->_desc = $value;
		return $this;
	}

	/** UI interpretation or CSS class(es) in HTML
	 *
	 * @param string $value
	 *
	 * @return string|QElem
	 */
	public function style($value = null)
	{
		if ($value === null) return $this->_style;
		$this->_style = $value;
		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return int|QElem
	 */
	public function order($value = null)
	{
		if ($value === null) return $this->_order;
		$this->_order = $value;
		return $this;
	}

	/** Gets or sets items for the type
	 *
	 * @param array|bool $value Values
	 *
	 * @return array|QElem
	 */
	public function items($value = false)
	{
		if ($value === false) return $this->_items;
		if (is_array($value)) {
			$arrType = Util::getArrayType($value);
			if ($arrType == 1) foreach ($value as $val) $this->_items[] = new QItem(["value" => $val, "text" => $val]);
			elseif ($arrType == 4) foreach ($value as $val) $this->_items[] = new QItem($val);
			else foreach ($value as $key => $val) $this->_items[] = new QItem(["value" => $key, "text" => $val]);
			return $this;
		} elseif (func_num_args() > 1) {
			$value = func_get_args();
			foreach ($value as $val) $this->_items[] = new QItem(["value" => $val, "text" => $val]);
			return $this;
		}
		foreach ($this->_items as $item) if ($item->value() == $value) return $item;
		return $this;
	}

	public function hasItems()
	{
		return !empty($this->_items);
	}

	public function hasValue()
	{
		return !empty($this->_values);
	}

	/** Set label
	 *
	 * @param string $value
	 *
	 * @return string|QElem
	 */
	public function label($value = null)
	{
		if ($value === null) return $this->_label;
		$this->_label = $value;
		return $this;
	}

	/** Set action
	 *
	 * @param string $value
	 *
	 * @return string|QElem
	 */
	public function action($value = null)
	{
		if ($value === null) return $this->_action;
		$this->_action = $value;
		return $this;
	}

	/** Set visibility (idea: just bool to hide the control/column)
	 *
	 * @param bool $value
	 *
	 * @return bool|QElem
	 */
	public function visible($value = null)
	{
		if ($value === null) return $this->mode() != ElemMode::hidden && $this->type() != ElemMode::hidden;
		$this->mode($value ? ElemMode::hidden : ElemMode::normal); // TODO: normal might replace previous mode setting!!
		return $this;
	}
}

abstract class ElemType extends QEnum
{
	const class_ = 1;
	const result = 3; /// Computed
	const const_ = 4; /// Value is not copied into object
	const enum = 6; /// Enum with elems or items

	const button = -1;

	const boolean = 10; /// 1/0 (as Yes/No) - checkbox
	const number = 12; /// Integer or Decimal number (MAIN)

	const longtext = 20;
	const text = 24; /// Any value (MAIN)

	const datetime = 30; /// YYYYMMDDhhmmss (MAIN)
	const time = 36; /// duration in decimal seconds (s.sss; output: 1d 16h 31m)

	const file = 50;

	const relation = 110;
}

abstract class ElemMode extends QEnum
{
	const hidden = 0; // Input type = hidden
	const disabled = 2; // Not editable
	const plain = 3; // Plain text, no control, not editable
	const normal = 4;
	const expected = 5; // Marked "+", must be filled to progress into next stage (like "validated")
	const required = 6; // Marked "*", must be filled to process (typically save) the form
	const unique = 8; // Unique value in the class it belongs to, it also is required (empty = not unique)
}

abstract class DateTimePrecision extends QEnum
{
	const century = 1;
	const decade = 2;
	const year = 3;
	const quarterYear = 4;
	const month = 5;
	const week = 6;
	const day = 7;
	const hour = 8;
	const minute = 9;
	const second = 10;
}
