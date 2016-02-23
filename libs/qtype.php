<?php namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.01.29 | QetriX Type
 */

class QType
{
	/* TODO:
	 * Date Format (custom)
	 * For value comparsion - is better bigger (speed, power, population, capacity) or smaller value (price, weight, acceleration, power intake)? Valid for calcualted values as well.
	*/

	// Class content
	protected $t_pk = null; // Primary Key, null = new (not in DS yet)
	protected $tn; // Lang key for name
	protected $tt_fk = null; // Parent type
	protected $tmo = 1; // Max occurences in class. -1 = is ent, 127 = unlimited.
	protected $toc = 0; // Order in class
	protected $tds; // DataStore: engine.particletable OR engine.table.column
	protected $tvt = ValueType::text; // Value type
	protected $tvu = null; // Value unit (SI, if possible)
	protected $tvn = null; // Min value (num) / min length (str)
	protected $tvx = null; // Max value (num) / max length (str)
	protected $tvp = null; // Value precision
	protected $tvv = null; // Value validation
	protected $tvm = 3; // Value mode (R/O, req, unique...)
	protected $trt = RelationType::none; // Relation type
	protected $trm = 0; // Relation mode
	protected $tot = OrderType::none; // Order type
	protected $tod = 1; // Default order for new particle

	function __construct($data = null)
	{
		return $this->QType($data);
	}

	public function QType($data = null)
	{
		foreach ($data as $k => $v) if (property_exists($this, $k)) $this->$k = $v;
		return $this;
	}

	public function id()
	{
		return $this->t_pk;
	}

	public function name($value = false)
	{
		if ($value === false) return $this->tn;
		$this->tn = $value;
		return $this;
	}

	public function valueType(ValueType $value = null)
	{
		if ($value === null) return $this->tvt;
		$this->tvt = $value;
		return $this;
	}

	/** Value precision
	 * Number: 100=hundreads, 10=tens, 0.1=1/10, 0.01=1/100
	 * DateTime: 1=year, 2=half, 3=quarter, 4=month, 5=week, 6=day, 7=hour, 8=minute, 9=second
	 * Time: 1=year, 2=half, 3=quarter, 4=month, 5=week, 6=day, 7=hour, 8=minute, 9=second, 10=1/10 sec, 11=1/100 sec, 12=1/1000 sec
	 */
	public function valuePrecision($value = null)
	{
		if ($value === null) return $this->tvp;
		if ($value == 0) $value = null;
		switch ($this->tvt) {
			case ValueType::dateTime:

				break;
		}
		$this->tvp = $value;
		return $this;
	}

	/** Value validation */
	public function valueValidation($value = null)
	{
		if ($value === null) return $this->tvv;
		$this->tvv = $value;
		return $this;
	}

	public function relationType(RelationType $value = null)
	{
		if ($value === null) return $this->trt;
		$this->trt = $value;
		return $this;
	}

	public function orderType(OrderType $value = null)
	{
		if ($value === null) return $this->tot;
		$this->tot = $value;
		return $this;
	}
}

final class ValueType
{
	/// Value interpretation

	const none = 0; // Particle has no value
	const entity = 1; // It's an entity
	const system = 2; // Particle value is handled by the system (e.g. calculated)

	const boolean = 10; // 1/0 (as Yes/No)
	const number = 12; // Integer or Decimal number (MAIN)
	const dateTime = 16; // YYYYMMDDhhmmss (MAIN)
	const time = 17; // hhmmss.sss, in decimal seconds as duration (output: 1d 16h 31m)

	const longTextTab = 20; // Any value, incl. tabs and line breaks
	const wikiText = 21; // Wiki text layout
	const longText = 22; // Anything, incl. line breaks
	const htmlText = 23; // HTML editor (WYSIWYG)
	const text = 24; // Any value (MAIN)
	const textLoc = 25; // Any value, parsed by localizator
	const valueList = 26; // Exact value from list
	const valueListLoc = 27; // Exact value, localizable

	const url = 30; // protocol://subdomain.domain.tld:port/page/file.ext#hash?param=value
	const email = 31; // name.surname@subdomain.domain.tld
	const password = 32; // Masked
	const newPassword = 33; // Masked, two pwd + strength meter

	const geoPoint = 40; // lat,lon,alt => if no geoParticle support installed
	const color = 41; // Color wheel / RGB / ...

	const file = 50; // Regular download link (MAIN)
	const fileWithPreview = 51; // Image download link
	const document = 52; // Indexable content
	const image = 53; // LightBox, with thumbnail
	const audio = 54; // Audio player
	const video = 55; // Video player

	const QetriXConn = 99; // Conn to remote QetriX entity

	public static function toArray()
	{
		return array_flip((new \ReflectionClass(new self))->getConstants());
	}
}

final class ValueMode
{
	const hidden = 0;
	const disabled = 2;
	//const readOnly = 3;
	const normal = 4;
	const required = 6;
	const uniqueInClass = 8;
	const unique = 9;

	public static function toArray()
	{
		return array_flip((new \ReflectionClass(new self))->getConstants());
	}
}

final class RelationType
{
	const none = 0;
	const system = 1;

	const autocomplete = 10; // Autocomplete from all (s%)
	const autocompleteMRU = 11; // Offer Most Recently Used
	const autocompleteMOU = 12; // Offer Most Often Used

	const autocompleteTt = 13; // Autocomplete from tt (s%)
	const autocompleteTtMRU = 14;
	const autocompleteTtMOU = 15;

	const suggest = 20; // %s%
	const suggestMRU = 21;
	const suggestMOU = 22;

	const suggestTt = 23; // %s%
	const suggestTtMRU = 24;
	const suggestTtMOU = 25;

	const listBoxClass = 30; //
	const listBoxEntAttribs = 31;
	const listBoxEntAttribsEt = 32;
	const listBoxEntRels = 33;
	const listBoxEntRelsEt = 34;

	const keyValue = 40; // As Maximo - dual field with textbox for both ID and Text (allows autocomplete of corresponding values for ID and Text as well), with button that brings up table

	public static function toArray()
	{
		return array_flip((new \ReflectionClass(new self))->getConstants());
	}
}

final class RelationMode
{
	const hidden = 0;

	const oneWay = 1;
	const oneWayDisabled = 2;
	const oneWayRequired = 3;

	// TODO: No, significant is NOT a way to tell QetriX THIS is the relation it should follow to find primary parent. I'm thinking about using ORDER instead, but how?
	//const oneWaySignificant = 4;
	//const oneWaySignificantReadOnly = 5;
	//const oneWaySignificantRequired = 6;

	// TODO: Unique use case?
	//const oneWayUnique = 7;
	//const oneWayUniqueReadOnly = 8;
	//const oneWayUniqueRequired = 9;

	// Use case: partners or spouses
	const twoWay = 11;
	const twoWayDisabled = 12;
	const twoWayRequired = 13;

	public static function toArray()
	{
		return array_flip((new \ReflectionClass(new self))->getConstants());
	}
}

final class OrderType
{
	const none = 0;
	const system = 1;
	const value = 2; // Order per value, ascending (lower better)
	const valueDesc = 3; // Order per value, descending (higher better)
	//const classOrder = 4; // List of values per classification
	const numericOrder = 5;
	const numericOrderDesc = 6;

	//const dateNoValidation = 10;
	//const dateNoValidationDesc = 11;
	const date = 12;
	const dateDesc = 13;
	//const dateTimeNoValidation = 14;
	//const dateTimeNoValidationDesc = 15;
	const dateTime = 16;
	const dateTimeDesc = 17;

	public static function toArray()
	{
		return array_flip((new \ReflectionClass(new self))->getConstants());
	}
}
