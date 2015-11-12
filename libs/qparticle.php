<?php namespace com\qetrix\libs;

/* Copyright (c) 2015 QetriX. Licensed under MIT License, see /LICENSE.txt file.
 * Particle Class
 */
require_once dirname(__FILE__)."/qtype.php";

class QParticle
{
	protected $id = null; // Primary key (PK), null = new (not in DS yet)
	protected $p; // Parent (p_pk of parent particle)
	protected $t; // Type (t_pk of type)
	protected $v = null; // Value
	protected $r = null; // Relation (p_pk of foreign Particle)
	protected $rv = null; // Relation value (pv of foreign Particle)
	protected $rt = null; // Relation type (pt of foreign Particle)
	protected $rtn = null; // Relation type (ptn of foreign Particle)
	protected $o = 0; // Order

	protected $type; // Type object (QType)

	function __construct($data = null)
	{
		return $this->particle($data);
	}

	public function particle($data = null)
	{
		if ($data !== null) {
			foreach ($data as $k=>$v) if (property_exists($this, $k)) $this->$k = $v;
			$this->type = new QType($data);
		}
		return $this;
	}

	public function id($value = false)
	{
		if ($value === false) return $this->id;
		if ($value < 0) throw new \Exception("p_pk can't be negative.");
		elseif ($value == 0) throw new \Exception("p_pk can't be zero.");
		$this->id = $value + 0;
		return $this;
	}

	public function parent($value = false)
	{
		if ($value === false) return $this->p;
		if ($value < 0) throw new \Exception("pp_fk can't be negative.");
		elseif ($value == 0) throw new \Exception("pp_fk can't be zero.");
		$this->p = $value + 0;
		return $this;
	}

	public function type($value = false)
	{
		if ($value === false) return $this->t;
		if (is_numeric($value)) {
			if ($value < 0) throw new \Exception("pt_fk can't be negative.");
			elseif ($value == 0) throw new \Exception("pt_fk can't be zero.");
			$this->t = $value;
		} elseif ($value instanceof QType) { // TODO: Check for QType type
			$this->t = $value->id();
			$this->type = $value;
		} else throw new \Exception($value." is not valid type identifier.");
		return $this;
	}

	public function typeName()
	{
		return $this->type->name();
	}

	public function value($value = false)
	{
		if ($value === false) return $this->v;
		$this->v = $value;
		return $this;
	}

	public function relation($value = false)
	{
		if ($value === false) return $this->r;
		if ($value <= 0) throw new \Exception("pr_fk can't be negative.");
		$this->r = $value;
		return $this;
	}

	public function relationValue()
	{
		return $this->rv;
	}

	public function order($value = false)
	{
		if ($value === false) return $this->o;
		$this->o = $value + 0;
		return $this;
	}
}
