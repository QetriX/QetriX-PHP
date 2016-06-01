<?php //declare(strict_types = 1);
namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.05.30 | DataStore Class
 */

use com\qetrix\libs\components\QForm;
use com\qetrix\libs\QPage;
use com\qetrix\libs\Util;

class DataStore
{
	protected $data = []; // Cache to store data loaded multiple times per page load

	protected $conn;
	protected $features = array(); // What features database has (tables, views, procedures...)
	protected $prefix = "";
	protected $ccDS; // Datastore to replicate set DS requests
	protected $scope = "";

	//protected $rows = null;
	//protected $cols = [];

	protected $_name = "";

	protected $stringChar = "'"; // Character for strings, usually single or double quotes

	/** Connect to DataStore - database or directory
	 *
	 * @param string $host Address of a database server, or path to a data directory, e.g. "localhost"
	 * @param string $scope Database name, SID, directory or file name, e.g. "qetrix"
	 * @param string $prefix Table prefix (allowing multiple apps in single database) or subdirectory, e.g. "" (empty string = no prefix)
	 * @param string $user User Name, Schema Name or subdirectory, e.g. "qetrix"
	 * @param string $password Password for $username, e.g. "******" :)
	 *
	 * @return $this
	 */
	public function conn($host, $scope, $prefix = "", $user = "", $password = "")
	{
		$this->prefix = $prefix;
		//$this->conn = new \PDO("mysql:host=".$host.";dbname=".$scope.";charset=utf8", $user, $password, array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING));
		return $this;
	}

	public function activate($scope)
	{
		$this->scope = $scope;
	}

	/**
	 * @param mixed $value
	 * @param string $type
	 *
	 * @return int|string
	 * @throws \Exception
	 */
	protected function sanitize($value, $type = "")
	{
		if ($value === null) return "NULL";
		if (is_array($value)) {
			foreach ($value as &$v) $v = $this->sanitize($v);
			return $value;
		}
		if ($type === "") $type = is_numeric($value) ? "num" : "str"; // TODO: ENUM for type

		switch ($type) {
			case "num":
			case "n":
				return $value === "" ? "NULL" : $value + 0;
			case "str":
			case "s":
			case "s*":
			case "*s*":
			case "*s":
				$value = str_replace("\\".$this->stringChar, $this->stringChar, $value);
				$value = str_replace("\\\\", "\\", $value);
				$value = str_replace("\\", "\\\\", $value);
				$value = str_replace($this->stringChar, "\\".$this->stringChar, $value);
				return $this->stringChar.($type == "*s" || $type == "*s*" ? "%" : "").$value.($type == "s*" || $type == "*s*" ? "%" : "").$this->stringChar;
				break;
			case "list":
				if (!is_array($value)) throw new \Exception("Value must be an array");
				$arr = array();
				foreach ($value as $v) $arr[] = $this->sanitize($v);
				return implode(", ", $arr);
			default:
				throw new \Exception("Invalid sanitize type: ".$type.(strpos($type, "%") > -1 ? ". Use ".str_replace("%", "*", $type)." instead." : ""));
		}
	}

	public function addFeature($feature)
	{
		$this->features[] = strToLower($feature);
		return $this;
	}

	public function addFeatures(array $feature)
	{
		$this->features = array_merge($this->features, $feature);
		return $this;
	}

	public function hasFeature($featureName)
	{
		return in_array(strToLower($featureName), $this->features);
	}

	/*public function addCol($colName, $colValue = null)
	{
		$cols[] = array($colName, $colValue);
		return $this;
	}*/

	public function prefix($value = false)
	{
		if ($value === false) return $this->prefix;
		$this->prefix = $value;
		return $this;
	}

	public function scopes()
	{
		return array_keys($this->data);
	}

	/** @param $value Array of strings (DS names) */
	public function cc(array $value)
	{
		$this->ccDS = $value;
	}

	public function name()
	{
		if ($this->_name != "") return $this->_name;
		$name = get_class($this);
		return strToLower(substr($name, strrpos($name, "\\") + 1));
	}

	public function get($scope)
	{
		return [];
	}

	public function set($sql, $text = null)
	{
		return $this;
	}

	public function form(QForm $form, $scope, $pkName, $pkValue = null, $setData = null)
	{
	}

	public function getLabels($langCode)
	{
		return [];
	}

	public function listApps()
	{
		return [];
	}
}
