<?php declare(strict_types = 1);
namespace com\qetrix\apps\common\converters;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.05.03 | QetriX Converter from QForm to HTML5 form
 */

use com\qetrix\libs\components\QForm;
use com\qetrix\libs\components\QForm\QFormControl;
use com\qetrix\libs\components\QForm\QFormControlMode;
use com\qetrix\libs\components\QForm\QFormControlType;
use com\qetrix\libs\QPage;
use com\qetrix\libs\Util;
use com\qetrix\libs\ValueType;

class QForm_Html
{
	const NL = "\n";

	private $order = -10;
	private $_hasFiles = false; // For adding enctype=multipart/form-data

	/** @var QForm form */
	private $form;

	public function convert(QForm $form, $args = array())
	{
		if (count($form->controls()) == 0) /*if (APP_STATE == \QState::Prod) return ""; else*/
			throw new \Exception("QForm \"".$form->name()."\" has no controls!");

		$this->form = $form;
		$str = "";

		// Form Heading
		if ($this->form->heading() != "") $str .= "<h3>".$this->form->heading()."</h3>".self::NL;

		// Form Controls
		foreach ($this->form->controls() as $control) {
			$str .= $this->part($control);
		}

		return $this->head().$str.$this->foot(); // Head added down here, because of enctype attribute
	}

	protected function head()
	{
		return "<form method=\"post\" action=\"".$this->form->actionPath()."\" target=\"".$this->form->target()."\" id=\"".$this->form->name()."\" class=\"qform\"".
		($this->form->action() != "" ? " onsubmit=\"".$this->settingsToJS($this->form->action())."\"" : "").
		($this->_hasFiles ? " enctype=\"multipart/form-data\"" : "").
		"><div>".self::NL;
	}

	protected function part(QFormControl $control)
	{
		$str = "";
		$order = floor($control->order() / $this->form->rpp()) + 1;
		if ($this->order != -10 && $this->order < $control->order()) $str .= "</div>".self::NL; // Closing tag for div.gfl

		if ($this->order == -10 || floor($this->order / $this->form->rpp()) + 1 < $order) {
			if ($this->order != -10) { // Closing tag for the "part" div below
				$str .= "</div>".self::NL;
				if (floor($this->order / $this->form->rpp() * 10) + 1 < floor($order / 10) + 1) $str .= "</div><div>";
			}

			$str .= "<div id=\"".$this->form->name()."_section".$order."\" class=\"qform_section\">".self::NL;

			/// Add section heading, if exists in lang, as: "Heading_" + div id (from generated HTML source code)
			$lbl = $this->form->page()->lbl("Heading_".$this->form->name()."_section".$order."");
			if (substr($lbl, 0, 1) != "-") $str .= ($this->form->heading() == "" ? "<h3>".$lbl."</h3>" : "<h4>".$lbl."</h4>").self::NL;
		}

		//if ($controls instanceof QFormControl) $controls = array($controls);
		if ($this->order < $control->order()) {
			$str .= "<div class=\"qfr qfc_".$control->type()."\">";
			if ($control->label() != "") $str .= "<label for=\"".$control->name().($control->type() == QFormControlType::rel ? "_text" : "")."\">".$control->label().($control->mode() == QFormControlMode::required ? "<span class=\"req\" title=\"".$this->form->page()->lbl("Required field")."\">*</span>" : "").":</label> ";
		}

		try {
			if ($control->type() == QFormControlType::multi) { // TODO: Better
				$str .= "<ul".($control->style() != "" ? " class=\"".$control->style()."\"" : "").">";
				$str .= $this->drawControl($control);
				$str .= "</ul>";

			} else {
				$str .= "<span".($control->style() != "" ? " class=\"".$control->style()."\"" : "").">";
				$str .= $this->drawControl($control);
				$str .= "</span>";
			}
		} catch (\Exception $ex) {
			Util::log($ex);
			// TODO
		}
		//if ($this->order < $order) $str .= "</div>".self::NL;

		$this->order = $control->order();

		return $str;
	}

	protected function drawControl(QFormControl $control)
	{
		// TODO: Allow extend this class to add more controls! This has to be some sort of dynamic, or in "default" branch it should call some method, that extends drawControl.
		switch ($control->type()) {
			case QFormControlType::text:
				return $this->textBox($control);
			case QFormControlType::longtext:
				return $this->longTextBox($control);
			case QFormControlType::wikitext:
				return $this->wikiTextBox($control);
			case QFormControlType::htmltext:
				return $this->htmlTextBox($control);
			case QFormControlType::hidden:
				return $this->hidden($control);
			case QFormControlType::email:
				return $this->email($control);
			case QFormControlType::plain:
				return $this->plain($control);
			case QFormControlType::rel:
			case QFormControlType::relation:
				return $this->textBoxRel($control);
			case QFormControlType::password:
				return $this->password($control);
			case QFormControlType::multi:
				return $this->multiBox($control);
			case QFormControlType::number:
				return $this->numBox($control);
			case QFormControlType::time:
				return $this->timeBox($control);
			case QFormControlType::checkbox:
				return $this->checkBox($control);
			case QFormControlType::button:
				return $this->button($control);
			case QFormControlType::file:
				return $this->file($control);
			case "list":
				return $this->dropDownList($control);
			case "date":
			case "datetime":
			case QFormControlType::datetime:
				return $this->datePicker($control);
			default:
				throw new \Exception("Err#xy: Control type \"".$control->type()."\" is not renderable in ".__CLASS__);
		}
	}

	private function getControlName(QFormControl $control)
	{
		return $control->formName()."-".$control->name(); //.($control->property() != "v" ? "_".$control->property() : "");
	}

	protected function hidden(QFormControl $control)
	{
		return "<input type=\"hidden\" name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\"".
		($control->value() != "" ? " value=\"".$control->value()."\"" : "").
		">";
	}

	protected function plain(QFormControl $control)
	{
		if ($control->action() != "") return strpos($control->action(), "//") !== false ? "<a href=\"#\" onclick=\"".$this->settingsToJS($control->action())."\">".$control->value()."</a>" : "<a href=\"".$control->action()."\">".$control->value()."</a>";
		return $control->value();
	}

	protected function email(QFormControl $control)
	{
		return "<input type=\"email\" name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\"".
		($control->value() != "" ? " value=\"".$control->value()."\"" : "").
		">";
	}

	protected function textBox(QFormControl $control)
	{
		// action = onkeyup
		return "<input type=\"text\" name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\"".
		($control->value() != "" ? " value=\"".$control->value()."\"" : "").
		($control->mode() == QFormControlMode::disabled ? " disabled" : "").
		($control->mode() == QFormControlMode::required ? " required" : "").
		($control->mode() == QFormControlMode::readonly ? " readonly" : "").
		($control->detail() != "" ? " placeholder=\"".$control->detail()."\"" : "").
		($control->action() != "" ? " onkeydown=\"".$this->settingsToJS($control->action())."\"" : "").
		">";
	}

	protected function textBoxRel(QFormControl $control)
	{
		// action = onkeyup
		return "<input type=\"hidden\" name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\"".
		($control->value() != "" ? " value=\"".$control->value()."\"" : "").
		">"."<input type=\"text\" name=\"".$this->getControlName($control)."_text\" id=\"".$this->getControlName($control)."_text\" autocomplete=\"off\"".
		($control->text() != "" ? " value=\"".$control->text()."\"" : "").
		($control->mode() == QFormControlMode::disabled ? " disabled" : "").
		($control->mode() == QFormControlMode::required ? " required" : "").
		($control->mode() == QFormControlMode::readonly ? " readonly" : "").
		($control->detail() != "" ? " placeholder=\"".$control->detail()."\"" : "").
		($control->action() != "" ? " onkeydown=\"".$this->settingsToJS($control->action())."\"" : "").
		">";
		//<input type=\"button\" class=\"btn\" value=\"&rsaquo;\" onclick=\"rel_goto();\">";
	}

	protected function longTextBox(QFormControl $control)
	{
		return "<textarea name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\">".$control->value()."</textarea>";
	}

	protected function wikiTextBox(QFormControl $control)
	{
		return "<textarea name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\">".$control->value()."</textarea>";
	}

	protected function htmlTextBox(QFormControl $control)
	{
		return "<script>var pathHtmlEditRes = \"".QPage::getInstance()->pathResCommon()."img/\";</script><script type=\"text/javascript\" src=\"".QPage::getInstance()->pathResCommon()."htmledit.js\"></script>
<script type=\"text/javascript\">
	bkLib.onDomLoaded(function() {
		var myNicEditor = new nicEditor();
		myNicEditor.addInstance(\"".$this->getControlName($control)."\");
		myNicEditor.setPanel(\"".$this->getControlName($control)."_htmlpanel\");
	});
</script>
<div id=\"".$this->getControlName($control)."_htmlpanel\"></div>
<textarea name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\" style=\"width:100%;height:400px;\">".$control->value()."</textarea>";
	}

	protected function password(QFormControl $control)
	{
		return "<input type=\"password\" name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\" value=\"".$control->value()."\" placeholder=\"".$control->detail()."\">";
	}

	protected function file(QFormControl $control)
	{
		$this->_hasFiles = true;
		return "<input type=\"file\" name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\">";
	}

	protected function button(QFormControl $control)
	{
		// action = onclick
		return "<input type=\"submit\" name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\" class=\"btn\" value=\"".$control->value()."\""
		.($control->action() != "" ? " onclick=\"".$this->settingsToJS($control->action())."\"" : "")
		.">";
	}

	protected function checkBox(QFormControl $control)
	{
		/// TODO: checkbox list from items

		$items = $control->items()->rows();
		if (count($items) > 0) {
			$str = "";
			foreach ($items as $item) {
				if ($item["value"] == "") continue;
				$val = explode("\t", $control->value());
				$str .= "<li><input type=\"checkbox\" name=\"".$control->name()."[]\" id=\"".$this->form->name()."_".$control->name()."_".$item["value"]."\" value=\"".$item["value"]."\"".(in_array($item["value"], $val) ? "checked" : "")."> <label for=\"".$control->name()."_".$item["value"]."\">".$item["text"]."</label></li>";
			}
			return $str;
		}

		return "<input type=\"checkbox\" name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\" value=\"1\"".($control->value() == "1" ? " checked" : "").">";
	}

	protected function multiBox(QFormControl $control)
	{
		$str = "";
		$items = $control->items()->rows();
		foreach ($items as $item) {
			if ($item["value"] == "") continue;
			$val = explode("\t", $control->value());
			$str .= "<li><input type=\"checkbox\" name=\"".$this->getControlName($control)."[]\" id=\"".$this->getControlName($control)."_".$item["value"]."\" value=\"".$item["value"]."\"".(in_array($item["value"], $val) ? "checked" : "")."> <label for=\"".$control->name()."_".$item["value"]."\">".$item["text"]."</label></li>";
		}
		return $str;
	}

	protected function dropDownList(QFormControl $control)
	{
		// action = onchange / selectedindexchange / SelectionChanged
		$options = "";
		if (count($control->items()) > 0) foreach ($control->items()->rows() as $item) {
			if (!isset($item["value"])) $item["value"] = $item["text"];
			$options .= "	<option value=\"".$item["value"]."\"".($control->value() == $item["value"] ? " selected" : "").">".$item["text"]."</option>".self::NL;
		}
		return "<select name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\"".
		($control->mode() == QFormControlMode::disabled ? " disabled" : "").
		($control->action() != "" ? " onchange=\"".$this->settingsToJS($control->action())."\"" : "").
		">".self::NL.$options."</select>";
	}

	protected function datePicker(QFormControl $control)
	{
		$format = "%3\$d.%2\$d.%1\$d %4\$02d:%5\$02d";
		switch ($control->valuePrecision()) {
			case 1:
				$format = "%3\$d";
				break;
			case 7:
				$format = "%3\$d.%2\$d.%1\$d";
				break;
			case 9:
				$format = "%3\$d.%2\$d.%1\$d %4\$02d:%5\$02d";
				break;
			case 10:
				$format = "%3\$d.%2\$d.%1\$d %4\$02d:%5\$02d:%6\$02d";
				break;
		}
		return "<input type=\"date\" name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\" value=\"".Util::formatDateTime($control->value(), $format)."\""
		.($control->mode() == QFormControlMode::disabled ? " disabled" : "")
		.($control->mode() == QFormControlMode::required ? " required" : "")
		.">";
	}

	protected function numBox(QFormControl $control)
	{
		return "<input type=\"text\" name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\" value=\"".$control->value()."\" autocomplete=\"off\""
		.($control->mode() == QFormControlMode::disabled ? " disabled" : "")
		.($control->mode() == QFormControlMode::required ? " required" : "")
		.">";
	}

	protected function timeBox(QFormControl $control)
	{
		$value = "";
		if ($control->value() != "") {
			$hrs = floor($control->value() / 60);
			$mns = $control->value() % 60;
			$value = $hrs.":".substr("00".$mns, -2);
		}

		return "<input type=\"text\" name=\"".$this->getControlName($control)."\" id=\"".$this->getControlName($control)."\" value=\"".$value."\"".
		($control->action() != "" ? " onchange=\"".$this->settingsToJS($control->action())."\"" : "").
		($control->mode() == QFormControlMode::disabled ? " disabled" : "").
		($control->mode() == QFormControlMode::required ? " required" : "").
		">";
	}

	// Form closing tags
	protected function foot()
	{
		return "</div></div>". // Because of sections in Part
		"</div></form>".self::NL;
	}

	// Convert action value to JavaScript code
	protected function settingsToJS($settings, array $data = array())
	{
		if ($settings == "") return null;
		if (strpos($settings, "\t") === false && strpos($settings, "/") !== false) return "return !goto('".Util::processVars($settings, $data)."');";
		$arr = Util::getQueRow($settings, "\t", $data);
		$func = array_shift($arr);
		$args = "this,event";
		foreach ($arr as $a) $args .= ",".(is_numeric($a) ? $a : "'".$a."'");
		return "return ".$func."(".$args.");";
	}
}
