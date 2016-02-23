<?php
namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.01.29 | Entity Class
 */

use com\qetrix\libs\Util;

require_once dirname(__FILE__)."/qparticle.php";

class QEntity extends QParticle
{
	protected $ar = array(); // Array of Particle arrays, or Dictionary<string, List<Particle>>
	protected $messages = array(); // Messages

	function __construct($data = null)
	{
		$this->QEntity($data);
	}

	function QEntity($data = null)
	{
		//Util::log($data);
		if ($data !== null) {
			if (is_numeric($data)) {
				$this->id($data);
			} elseif (is_string($data)) {
				$this->name($data);
			} elseif (is_array($data)) {
				foreach ($data as $v) {
					if ($this->id === null) {
						$this->particle($v);
					} else {
						if (isset($this->ar[strToLower($v["tn"])])) $this->ar[$v["tn"]] = array();
						$this->ar[strToLower($v["tn"])][] = new QParticle($v);
					}
				}
			} else throw new \Exception("Invalid QEntity.data type! Integer or array expected, got ".gettype($data).".");
		}
	}

	function get($name, $property = "v", $nvl = null)
	{
		if (!isset($this->ar[$name]) || !isset($this->ar[$name][0])) return $nvl; //throw new \Exception("Entity or relation of type \"".$name."\" doesn't exist in Entity \"".$this->pv."\" (#".$this->p_pk.")");
		switch ($property) {
			case "v":
				if ($this->ar[$name][0]->value() == "" && $nvl !== null) return $nvl;
				return $this->ar[$name][0]->value();
			case "r":
				return $this->ar[$name][0]->relation();
			case "rv":
				return $this->ar[$name][0]->relationValue();
			case "o":
				return $this->ar[$name][0]->order();
			case "p":
				return $this->ar[$name][0]->parent();
			case "id":
				return $this->ar[$name][0]->id();
			case "t":
				return $this->ar[$name][0]->type();
			case "tn":
				return $this->ar[$name][0]->typeName();
		}
		return null;
	}

	function getParticle($name, $index = 0)
	{
		if (!isset($this->ar[$name]) || !isset($this->ar[$name][$index])) return null; //throw new \Exception("Entity or relation of type \"".$name."\" doesn't exist in Entity \"".$this->pv."\" (#".$this->p_pk.")");
		return $this->ar[$name][$index];
	}

	function getList()
	{
		return array_keys($this->ar);
	}

	function message($body)
	{
		$this->messages[] = array("body" => $body);
	}

	function name()
	{
		return $this->value();
	}
}
