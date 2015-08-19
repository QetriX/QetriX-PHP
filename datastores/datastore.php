<?php namespace com\qetrix\datastores;

/* Copyright (c) 2015 QetriX. Licensed under MIT License, see /LICENSE.txt file.
 * DataStore Class
 */

use com\qetrix\libs\QApp;
use com\qetrix\libs\Util;

class DataStore
{
	/** @var QApp $app */
	protected $app;
	protected $data = array(); // Cache to store data loaded multiple times per page load

	// See function conn for description
	protected $host;
	protected $db;
	protected $prefix;
	protected $username;
	protected $password;

	protected $conn;
	protected $features = array(); // What features database has (tables, views, procedures...)

	protected $rows = null;
	protected $cols = array();

	protected $link_base;
	protected $link_res;

	/** PHP constructor */
	public function __construct(QApp $app)
	{
		$this->DataStore($app);
	}

	/** DataStore constructor */
	public function DataStore(QApp $app)
	{
		$this->app = $app;
		$this->data = array();
	}

	/** Connect to DataStore - database or directory
	 * @param string $host Address of a database server, or path to a data directory, e.g. "localhost"
	 * @param string $db Database name, SID, directory or file name, e.g. "qetrix"
	 * @param string $prefix Table prefix (allowing multiple apps in single database) or subdirectory, e.g. "" (empty string = no prefix)
	 * @param null $username User Name, Schema Name or subdirectory, e.g. "qetrix"
	 * @param null $password Password for $username, e.g. "******" :)
	 *
	 * @return $this
	 */
	public function conn($host, $db, $prefix = "", $username = null, $password = null)
	{
		$this->host = $host;
		$this->db = $db;
		$this->prefix = $prefix;
		$this->username = $username;
		$this->password = $password;

		$this->conn = null;
		return $this;
	}

	public function activate($db)
	{
		$this->db = $db;
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
				return $value + 0;
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
