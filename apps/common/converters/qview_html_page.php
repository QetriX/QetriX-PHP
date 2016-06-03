<?php declare(strict_types = 1);
namespace com\qetrix\apps\common\converters;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.05.20 | QView to HTML Page Converter
 */

use com\qetrix\libs\QPage;
use com\qetrix\libs\components\qview;
use com\qetrix\libs\Util;

class QView_Html_Page
{
	const NL = "\n";

	/** @var QVIew $QView */
	protected $QView = null;
	/** @var QPage $app */
	protected $_page;
	protected $_image;

	/** PHP Constructor */
	function __construct()
	{
		$this->QView_HtmlPage();
	}

	protected function page()
	{
		return $this->_page;
	}

	/** Constructor */
	function QView_HtmlPage()
	{
		$this->_page = QPage::getInstance();
		//$this->app()->envDS()->set("header", "X-UA-Compatible", "IE=edge,chrome=1");
		//$this->app()->envDS()->set("header", "content", "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no");

		// TODO!!!
		//$this->SD = $this->app()->get("dir")."res/";
		//$this->XD = $this->app()->get("dir_app")."res/";
		//$XD = "/qetrix/apps/".$this->app()->name."/res/";
	}

	/** Converts QView to HTML */
	public function convert(QView $view)
	{
		$this->QView = $view;

		$html = "";
		$html .= $this->head();
		// if ($QView->Heading() != "") $html .= $this->Heading($QView->Heading()); // 20150123 MNo: This way it might be as well a part of Head, pointless

		if ($view->sections() === null) throw new \Exception("Err#xy: No sections in view \"".$view->name()."\"");


		foreach ($view->sections() as $section) {
			$html .= $this->section($section."");
		}
		//$html = preg_replace("/<h3>/", "<h2>", preg_replace("/<\\/h3>/", "</h2>", $html, 1), 1); // TODO, creates H2 (page name) from the first H3
		$html .= $this->foot();
		return $html;
	}

	/** HTML page head */
	protected function head()
	{
		return "<!DOCTYPE html>
<html>
<head>
	<meta charset=\"UTF-8\" />
	<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge,chrome=1\">
	<title>".($this->QView->heading() != "" && $this->QView->heading() != $this->page()->text() ? $this->QView->heading()." - " : "").$this->page()->text()."</title>
	<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
	<link rel=\"stylesheet\" href=\"".$this->page()->pathResCommon()."common.css\" type=\"text/css\" media=\"all\">
	<link rel=\"stylesheet\" href=\"".$this->page()->pathRes().$this->page()->appName().".css\" type=\"text/css\" media=\"all\">
	<link rel=\"stylesheet\" href=\"".$this->page()->pathResCommon()."print.css\" type=\"text/css\" media=\"print\">
	<link rel=\"icon\" type=\"image/x-icon\" href=\"".$this->page()->pathRes()."favicon.ico?v=3\">
</head>
<body><a name=\"pt\"></a>
<div id=\"".$this->QView->name()."\" class=\"page loading".($this->QView->style() != "" ? " ".$this->QView->style() : "")."\">
<h1>".(($this->page()->path() != "/" && $this->page()->path() != "") || $this->page()->hasFormData() ? "<a href=\"".$this->page()->pathBase()."\">".$this->page()->text()."</a>" : "<span>".$this->page()->text()."</span>")."</h1>".self::NL
		.($this->QView->heading() != "" ? "<h2>".$this->QView->heading()."</h2>".self::NL : "")
		.($this->image() !== null ? "<div id=\"page_image\" style=\"background-image:url(".$this->image().");\"></div>".self::NL : "");
	}

	/** HTML page heading */
	/*public function heading($heading, $image = null)
	{
		if ($heading == null) return "";
		return "<h2>".$heading."</h2>".self::NL.($image !== null ? "<div id=\"page_image\" style=\"background-image:url(".$image.");\"></div>".self::NL : "");
	}*/

	protected function section($contents, $name = null, $heading = null, $class = null)
	{
		// if (is_object($contents)) $contents = $this->app()->convert($contents, "html"); // -20150413 MNo: There's no App->convert()
		// if ($name === null && $heading === null) return $contents.self::NL; -MNo 20160421

		return $contents == "" ? "" : "<div".($name !== null ? " id=\"".$name."\"" : "").($class !== null ? " class=\"".$class."\"" : "").">".self::NL
			.($heading !== null ? "<h3>".$heading."</h3>".self::NL : "")
			.$contents
			."</div>".self::NL;
	}

	protected function image($value = null)
	{
		if ($value === null) return $this->_image;
		$this->_image = $value;
		return $this;
	}

	/** HTML page foot */
	protected function foot()
	{
		return "</div>
<div id=\"mw\"><div id=\"mwc\"></div></div><div id=\"ac\"></div><div id=\"msg\"></div>
<script type=\"text/javascript\">"
		."var pathBase='".$this->page()->pathBase()."',"
		."username='".($this->page()->auth() != null ? $this->page()->auth()->username() : "")."';"
		."</script>
<script type=\"text/javascript\" src=\"".$this->page()->pathResCommon()."common.js\"></script>
<script type=\"text/javascript\" src=\"".$this->page()->pathRes()."".$this->page()->appName().".js\"></script>
</body>
</html>";
	}
}
