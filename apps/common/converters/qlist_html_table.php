<?php declare(strict_types = 1);
namespace com\qetrix\apps\common\converters;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.05.17 | Converter from QList to HTML table
 */

use com\qetrix\libs\components\QList;
use com\qetrix\libs\components\qlist\QListColType;
use com\qetrix\libs\Dict;
use com\qetrix\libs\Util;

class QList_Html_Table
{
	const NL = "\n";

	/** @var QList null */
	protected $_list = null;

	public function convert(QList $list, Dict $args = null)
	{
		if ($list->rows() == [0 => []]) return "";
		$this->_list = $list;

		$str = "";
		$str .= $this->head();
		$section = "";
		foreach ($this->_list->rows() as $row) {
			if (count($this->_list->section()) > 0 && $section != $row[$this->_list->section()["value"]]) {
				$str .= $this->section($this->_list->sectionText($row));
				$section = $row[$this->_list->section()["value"]];
			}
			/// TODO: SECTIONS
			$str .= $this->part($row);
		}
		$str .= $this->foot();
		return $str;
	}

	/** Table starting tags and heading row */
	protected function head()
	{
		$str = "";
		$cls = [];
		if ($this->_list->action() != "") $cls[] = "rowaction";
		if ($this->_list->style() != "") $cls[] = $this->_list->style();
		if ($this->_list->heading() != "") $str = "<h3>".$this->_list->heading()."</h3>";
		$str .= "<table id=\"".$this->_list->name()."\"".(count($cls) > 0 ? " class=\"".implode(" ", $cls)."\"" : "").">".self::NL;

		$useHeading = false;
		$strh = "<thead><tr>";
		foreach ($this->_list->cols() as $col) {
			if (substr($col["name"], 0, 1) == "_") continue; /// Do not show cols with name starting with "_"
			$heading = trim((isset($col["heading"]) ? $col["heading"] : $col["name"]));
			if ($heading != "") $useHeading = true;
			$strh .= "<th>".$heading."</th>";
		}
		$strh .= "</tr></thead>";
		$str .= ($useHeading ? $strh : "")."<tbody>".self::NL;
		return $str;
	}

	protected function section($section)
	{
		return "<tr class=\"section\"><td colspan=\"".count($this->_list->cols())."\">".$section."</td></tr>".self::NL;
	}

	/** Table rows */
	protected function part($row)
	{
		$style = [];
		if (isset($row["style"])) $style[] = $row["style"];
		if (isset($row["selected"])) $style[] = "sel"; // Is selected
		if (isset($row["value"]) && is_array($row["value"])) $row["value"] = "Array";

		$str = "<tr".
			(isset($row["value"]) ? " data-value=\"".$row["value"]."\"" : "").
			(count($style) > 0 ? " class=\"".implode(" ", $style)."\"" : "").
			($this->_list->action() != "" ? " onclick=\"".$this->actionToJS($this->_list->action(), $row)."\"" : "").">";

		foreach ($this->_list->cols() as $col) {
			if (substr($col["name"], 0, 1) == "_") continue; // Do not show cols with name starting with "_"
			if (!isset($row[$col["name"]])) $row[$col["name"]] = ""; // Add missing cell

			$style = [];
			if (isset($col["action"]) && strpos($this->_list->actionBase().$col["action"], "/") === false) $style[] = "act"; // Has JS action
			if (isset($col["type"])) switch ($col["type"]) {
				case QListColType::number:
					$style[] = "num";
					break;
			}
			$classTag = count($style) > 0 ? " class=\"".implode(" ", $style)."\"" : "";
			$text = isset($col["type"]) ? $this->format($row[$col["name"]], $col["type"]) : $row[$col["name"]];
			if (isset($col["action"])) {
				if (isset($row["selected"])) $str .= "<td".$classTag."><span>".(isset($col["text"]) ? Util::processVars($col["text"], $row) : $text)."</span></td>";
				elseif (strpos($this->_list->actionBase().$col["action"], "/") === false) $str .= "<td onclick=\"".$this->actionToJS($col["action"], $row)."\"".$classTag.">".(isset($col["text"]) ? Util::processVars($col["text"], $row) : $text)."</td>";
				else $str .= "<td".$classTag."><a href=\"".Util::processVars($this->_list->actionBase().$col["action"], $row)."\">".(isset($col["text"]) ? Util::processVars($col["text"], $row) : $text)."</a></td>";
			} elseif (isset($row["_action"])) $str .= "<td".$classTag."><a href=\"".$this->_list->actionBase().$row["_action"]."\">".$text."</a></td>";
			elseif (isset($col["row"])) $str .= "<td".$classTag.">".Util::processVars($col["row"], $row)."</td>";
			else $str .= "<td".$classTag.">".$text."</td>";
		}
		$str .= "</tr>".self::NL;
		return $str;
	}

	// Table closing tags
	protected function foot()
	{
		return "</tbody></table>".self::NL;
	}

	protected function format($value, $type)
	{
		switch ($type) {
			case QListColType::number:
				return Util::formatNumber($value);
			case QListColType::date:
				return Util::formatDateTime($value, "%3\$d.%2\$d.%1\$d");
			case QListColType::datetime:
				return Util::formatDateTime($value);
		}
		return $value;
	}

	protected function oldformat($settings, $data) // TODO!!! Zobecnit, případně sloučit pro všechny komponenty (base class Component).
	{
		$arr = explode("\t", $settings);
		if (count($arr) == 0) return Util::processVars($settings, $data);
		switch (strToLower($arr[0])) {
			case "dateformat":
				return Util::formatDateTime($data[str_replace("%", "", $arr[1])], $arr[2]);
		}
		return Util::processVars($settings, $data);
	}

	// Convert action value to JavaScript code
	protected function actionToJS($settings, array $data = null)
	{
		if ($settings == "") return null;
		if (strpos($settings, "\t") === false && strpos($settings, "/") !== false) return "return !goto('".Util::processVars($settings, $data)."');";
		$arr = Util::getQueRow($settings, "\t", $data);
		$func = array_shift($arr);
		$args = "this,event";
		foreach ($arr as $a) $args .= ",".(is_numeric($a) ? $a : "'".$a."'");
		return "return !".$func."();";
	}
}
