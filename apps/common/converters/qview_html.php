<?php declare(strict_types = 1);
namespace com\qetrix\apps\common\converters;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.02.22 | QView to HTML Section Converter
 */

use com\qetrix\libs\components\qview;
use com\qetrix\libs\Util;

class QView_Html
{
	/** @var QVIew */
	private $QView = null;
	const NL = "\n";

	/** Converts QView to HTML */
	public function convert(QView $view, $args = [])
	{
		$this->QView = $view;
		if (count($view->sections()) == 0) throw new \Exception("Empty view: ".$view->name());

		$html = "";
		$html .= "<div id=\"".$view->name()."\"".($view->style() != "" ? " class=\"".$view->style()."\"" : "").">";
		if ($view->heading() != "") $html .= "<h3>".$view->heading()."</h3>".self::NL;
		foreach ($view->sections() as $section) {
			$html .= $this->section($section);
		}
		$html .= "</div>";
		return $html;
	}

	protected function section($contents)
	{
		//if ($name === null && $heading === null) return $contents;
		return $contents == "" ? "" : "<div".
			//($name !== null ? " id=\"".$name."\"" : "").
			//($cls !== null ? " class=\"".$cls."\"" : "").
			($this->QView->action() != "" ? " onclick=\"".$this->settingsToJS($this->QView->action())."\"" : "").
			">".self::NL.
			//($heading !== null ? "<h3>".$heading."</h3>".self::NL : "").
			$contents.
			"</div>".self::NL;
	}

	// Convert action value to JavaScript code
	private function settingsToJS($settings, array $data = array())
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
