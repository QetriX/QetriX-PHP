<?php declare(strict_types = 1);
namespace com\qetrix\apps\common\converters;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.06.03 | QList to JSON Converter
 */

use com\qetrix\libs\components\QForm;
use com\qetrix\libs\QModuleStage;


class QForm_Json
{
	const NL = "\n";

	public function convert(QForm $form)
	{
		//header('Content-Type: application/json');
		//$str = "for(;;);";
		if (count($form->controls()) == 0) if ("TODO" == QModuleStage::prod) return ""; else throw new \Exception("QForm \"".$form->name()."\" has no controls!");

		$str = "";
		$str .= "\"".$form->name()."\":{".self::NL;
		$s = array();
		foreach ($form->controls() as $control) {
			$s[] = $this->part($form, $control);
		}
		$str .= implode(",", $s);
		$str .= "}".self::NL;
		return $str;
	}

	protected function part(QForm $form, QForm\QFormControl $control)
	{
		$str = "\"".$control->name()."\":{".self::NL;
		$s = array();
		if ($control->type() != "") $s[] = "\"type\":\"".$control->type()."\"";
		if ($control->label() != "") $s[] = "\"label\":\"".$control->label()."\"";
		if ($control->value() != "") $s[] = "\"value\":\"".$control->value()."\"";
		if ($control->text() != "") $s[] = "\"text\":\"".$control->text()."\"";
		if ($control->mode() != "") $s[] = "\"mode\":\"".$control->mode()."\"";
		$str .= implode(",", $s);
		$str .= "}".self::NL;
		return $str;
	}
}
