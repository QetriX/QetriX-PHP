<?php declare(strict_types = 1);
namespace com\qetrix\apps\common\converters;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.06.03 | Converter from QList to HTML unordered list
 */

use com\qetrix\libs\components\QList;
use com\qetrix\libs\Dict;
use com\qetrix\libs\Util;

class QList_Html
{
	const NL = "\n";

	public function convert(QList $list, Dict $args = null)
	{
		$str = "";
		if ($list->heading() !== null && $list->heading() !== "") $str .= "<div id=\"".$list->name()."_div\"><h3>".$list->heading()."</h3>";
		$str .= "<ul".($list->name() != "" ? " id=\"".$list->name()."\"" : "")
			.($list->style() != "" ? " class=\"".$list->style()."\"" : "")
			.($list->action() != "" ? " onclick=\"".$this->actionToJS($list->action())."\"" : "")
			.">".self::NL;
		$str .= $this->items($list->rows(), $list);
		$str .= "</ul>";
		if ($list->heading() !== null && $list->heading() !== "") $str .= "</div>";
		return $str.self::NL;// console.log(event.originalTarget.getAttribute('data-value'));return false;
	}

	function items($items, QList $list, $level = 1)
	{
		$str = "";
		if ($level > 1) $str .= self::NL.str_repeat("\t", $level)."<ul>".self::NL;
		//Util::log($items, "QList_Html.items");
		foreach ($items as $item) {
			if (!isset($item["text"])) continue;
			$style = [];
			if (isset($item["selected"]) && ($item["selected"] === "1" || $item["selected"] + 0 == 1)) $style[] = "sel";
			if (isset($item["style"])) $style[] = $item["style"];

			$action = isset($item["action"]) ? ($item["action"] != "" ? $list->actionBase().$item["action"] : $list->actionBase()) : "";
			$str .= str_repeat("\t", $level);
			$str .= "<li".(count($style) > 0 ? " class=\"".implode(" ", $style)."\"" : "").">";
			if ($action != "" && strpos($action, "/") === false) { /// "action" is not a link, but a JavaScript onclick function
				$str .= "<a href=\"#\" onclick=\"".$this->actionToJS($action, $item)."\">".$item["text"]."</a>";
			} else if ($action != "" && $list->action() != "") { /// "action" is a link
				$str .= "<a href=\"#\"".(isset($item["value"]) ? " data-value=\"".$item["value"]."\" " : "")."data-text=\"".$item["text"]."\">".$item["text"]."</a>";
			} else /// Not a link
				$str .= ($action == "" || (isset($item["action"]) && $item["action"] == $list->value()) ? "<span>".$item["text"]."</span>" : "<a href=\"".$action."\">".$item["text"]."</a>");
			if (isset($item["items"]) && count($item["items"]) > 0) $str .= $this->items($item["items"], $list, $level + 1).str_repeat("\t", $level);
			$str .= "</li>".self::NL;
		}
		if ($level > 1) $str .= str_repeat("\t", $level)."</ul>".self::NL;
		return $str;
	}

	private function actionToJS($settings, array $data = null)
	{
		if ($settings == "") return "";
		$arr = Util::getQueRow($settings, "\t", $data);
		$func = array_shift($arr);
		$args = "this,event";
		foreach ($arr as $a) $args .= ",".(is_numeric($a) ? $a : "'".$a."'");
		return "return !".$func."();";
	}
}
