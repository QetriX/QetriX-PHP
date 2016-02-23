<?php
namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.01.29 | DataStore Class
 */

use com\qetrix\libs\QApp;
use com\qetrix\libs\Util;

class DataStore
{
	/** @var QApp $app */
	protected $_app;
	protected $data = array(); // Cache to store data loaded multiple times per page load

	protected $conn;
	protected $features = array(); // What features database has (tables, views, procedures...)
	protected $prefix;
	protected $syncPaths; // Path with remote QetriX
	protected $scope;

	protected $rows = null;
	protected $cols = array();

	protected $_remotePath;


	/** PHP constructor */
	public function __construct(QApp $app)
	{
		$this->DataStore($app);
	}

	/** DataStore constructor */
	public function DataStore(QApp $app)
	{
		$this->_app = $app;
		$this->data = array();
	}

	/** Connect to DataStore - database or directory
	 *
	 * @param string $host Address of a database server, or path to a data directory, e.g. "localhost"
	 * @param string $scope Database name, SID, directory or file name, e.g. "qetrix"
	 * @param string $prefix Table prefix (allowing multiple apps in single database) or subdirectory, e.g. "" (empty string = no prefix)
	 * @param null $user User Name, Schema Name or subdirectory, e.g. "qetrix"
	 * @param null $password Password for $username, e.g. "******" :)
	 *
	 * @return $this
	 */
	public function conn($host, $scope, $prefix = "", $user = null, $password = null)
	{
		$this->prefix = $prefix;
		//$this->conn = new \PDO("mysql:host=".$host.";dbname=".$scope.";charset=utf8", $user, $password, array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING));
		return $this;
	}

	public function activate($db)
	{
		$this->scope = $db;
	}

	/**
	 * @param mixed $value
	 * @param null $type
	 *
	 * @return int|string
	 * @throws \Exception
	 */
	protected function sanitize($value, $type = null)
	{
		if ($value === null) return "NULL";
		if ($type === null) $type = is_numeric($value) ? "num" : "str"; // TODO: ENUM for type

		switch ($type) {
			case "num":
			case "n":
				return $value === "" ? "NULL" : $value + 0;
			case "str":
			case "s":
			case "s*":
			case "*s*":
			case "*s":
				$value = str_replace("\\'", "'", $value);
				$value = str_replace("\\\\", "\\", $value);
				$value = str_replace("\\", "\\\\", $value);
				$value = str_replace("'", "\\'", $value);
				return "'".($type == "*s" || $type == "*s*" ? "%" : "").$value.($type == "s*" || $type == "*s*" ? "%" : "")."'";
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

	protected function app()
	{
		return $this->_app;
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

	public function addCol($colName, $colValue = null)
	{
		$cols[] = array($colName, $colValue);
		return $this;
	}

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

	/** Array of paths */
	public function sync(array $value)
	{
		// TODO: Verify paths
		$this->syncPaths = $value;
	}

	/*public function form($tableNameOrClassId)
	{
		if (is_numeric($tableNameOrClassId) && $this->hasFeature("t")) {
			if ($this->hasFeature("tt") && method_exists($this, "formClassMulti")) return $this->formClassMulti($tableNameOrClassId);
			else if (method_exists($this, "formClass")) return $this->formClass($tableNameOrClassId);
			throw new \Exception("Undefiend method ".Util::getClassName($this).".formClass");
		} else if (method_exists($this, "formTable")) return $this->formTable($tableNameOrClassId);
		throw new \Exception("Undefiend method ".Util::getClassName($this).".formTable");
	}*/
}
