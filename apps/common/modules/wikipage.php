<?php declare(strict_types = 1);
namespace com\qetrix\apps\common\modules;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.06.01 | WikiPage Module
 */

use com\qetrix\libs\components\qform;
use com\qetrix\libs\components\qform\QFormControl;
use com\qetrix\libs\components\qform\QFormControlType;
use com\qetrix\libs\components\qlist;
use com\qetrix\libs\components\QView;
use com\qetrix\libs\Dict;
use com\qetrix\libs\QEntity;
use com\qetrix\libs\QModule;
use com\qetrix\libs\QModuleStage;
use com\qetrix\libs\QPage;
use com\qetrix\libs\Util;

class WikiPage extends QModule
{
	/** @var QEntity */
	protected $wikiPage;
	protected $pageTitle;
	protected $pagePath;
	private $editable = true;

	/** PHP Constructor */
	public function __construct(QPage $page)
	{
		$this->WikiPage($page);
	}

	/** Constructor */
	public function WikiPage(QPage $page)
	{
		parent::QModule($page);
		$this->dataDS = $this->page()->ds();;
		$this->contentDS = $this->page()->ds();
	}

	public function main(Dict $args)
	{
		//if (!isset($this->page()->path()) || $this->page()->path() == "") $this->page()->path() = $this->app()->title();
		$this->loadPage(); // +MNo 20160601
		return $this->QPage($args, $this->midCol($args).
			(method_exists($this, "rightCol") ? $this->rightCol($args) : "").
			(method_exists($this, "leftCol") ? $this->leftCol($args) : "")
		);
	}

	function QPage(Dict $args, $contents, $heading = "", $style = "")
	{
		$page = new QView("wikipage", ($this->wikiPage !== null && $this->wikiPage->value() != "" ? $this->pageTitle." - " : "").$this->page()->text());
		if ($style != "") $page->style($style);
		$page->heading($this->pageTitle != "" ? $this->pageTitle : $this->page()->text());

		//if (APP_STATE == \QState::Prod) $page->add($this->Analytics());
		$page->add($contents);

		$header = new QView("header");
		$header->add($this->headerSearch($args));
		$header->add($this->headerFB());
		$header->add($this->headerNav());
		$page->add($header->convert());

		$page->add($this->footer($args));

		return $page->convert("page");
	}

	function analytics()
	{
		return "";
	}

	function headerNav()
	{
		$navList = new QList("headnav");
		$navList->actionBase($this->page()->path());
		if (method_exists($this->ds(), "customTopNav")) $navList->rows($this->ds()->customTopNav()); // TODO: Define current page, howto n-th level (highlight all parents as well) => CrumbNav
		$navList->actionBase($this->page()->pathBase());
		return $navList->convert();
	}

	function headerFB()
	{
		return "";
	}

	function headerTW()
	{
		return "";
	}

	function headerSearch(Dict $args)
	{
		$form = new QForm("searchForm");
		$form->actionPath($this->page()->path());
		$form->add((new QFormControl(QFormControlType::text, "q", "search", "", 10))->text("Hledání článků")); // TODO: lang
		$form->add(new QFormControl(QFormControlType::button, "search", "", "Search", 10));
		return $form->convert();
	}

	function midCol(Dict $args)
	{
		if ($this->page()->getFormData("searchform-search") != "") return $this->search($args);
		if ($this->wikiPage !== null) {
			try {
				$data = $this->dataDS->getData($this->pagePath)[0]["dv"];
			} catch (\Exception $ex) {
				return "<br>No contents for ".$this->pageTitle." found!"; // TODO: HTML!
			}
		} else {
			$this->page()->set("status", "404 Not Found", "");
			//header($this->app()->get("server_protocol")." 404 Not Found");
			return "404 Not Found";
		}

		$view = new QView("midcol");

		$published = null;
		if ($this->wikiPage->get("wikipage", "o") != "") $published = $this->wikiPage->get("wikipage", "o");
		elseif ($this->wikiPage->get("valid_from") != null) $published = $this->wikiPage->get("valid_from");
		$view->add("Back", "back");
		$view->add(Util::formatDateTime($published, '%3$d. %2$d. %1$d'), "published");
		$view->add(Util::convert($data, "wiki", "html"));
		return $view->convert();
	}

	function search(Dict $args)
	{
		$search = $this->page()->getFormData("searchform-q");
		if ($search == "") { // Go to random page
			$data = $this->ds()->randomPage();
			$this->page()->set("location", $this->page()->get("pathFull").$data["path"]);
			die;

		} else { // Show search results
			$rows = $this->ds()->search($search);
			if (count($rows) == 0) {
				$view = new QView("results");
				$view->style("notfound");
				$view->heading("Results for ".$search.":");
				$view->add("Nothing found.");
				return $view->convert();
			} elseif (count($rows) == 1) {
				$this->page()->set("location", $this->page()->get("pathFull").$rows[0]["action"]);
				die;
			} else {
				$list = new QList("results");
				$list->heading("Results for ".$search.":");
				$list->rows($rows);
				return $list->convert("tableview");
			}
		}
	}

	/*function getpageid()
	{
		echo $this->page()->get("id");
	}*/

	function _new(Dict $args)
	{
		if ($this->stage() == QModuleStage::prod) return ""; // Can't edit on PROD, it's unsafe

		$page = new QView("newpageview", "New page");

		if ($this->page()->getFormData("newpage-pagepath") != "") {
			$this->ds()->setPage($this->page()->getFormData("newpage-pagepath"), $this->page()->getFormData("newpage-pagename"), str_replace("-", "", $this->page()->getFormData("newpage-pagedate") + 0));
			$page->add("<a href=\"".$this->page()->pathBase().$this->page()->getFormData("newpage-pagepath")."/edit\" onclick=\"return !window.open(this.href);\" style=\"padding:6px 12px;clear:both;float:left;width:100%;\">EDIT: ".$this->page()->getFormData("newpage-pagename")."</a>");
		}
		$path = $this->page()->path() != "new" ? $this->page()->path() : "";
		//if (isset($this->page()->get(0))) $path = substr($path, 0, strrpos($path, "/"))."/";
		if ($this->page()->getFormData("newpage-pagepath") != "") $path = $this->page()->getFormData("newpage-pagepath");

		$form = new QForm("newpage");
		$form->add((new QFormControl("text", "pagename", "Name"))->mode(qform\QFormControlMode::required));
		$form->add(new QFormControl("text", "pagepath", "Path", $path));
		$form->add(new QFormControl("date", "pagedate", "Date", $this->page()->getFormData("newpage-pagedate")));
		$form->add(new QFormControl("button", "create", null, "Create"));
		$page->add($form->convert());

		return $this->QPage($args, $page->convert());
	}

	function pagelist(Dict $args)
	{
		$list = new QList("midcol");
		$list->style("pdlist");
		$list->addCol("heading", "", "%path%");
		$list->addCol("published", "");
		$list->add($this->ds()->get("getfullpages", null, 10));

		return $this->QPage($args, $list->convert("blogposts")
			.(method_exists($this, "rightCol") ? $this->rightCol() : "")
			.(method_exists($this, "leftCol") ? $this->leftCol() : ""),
			"bplist"
		);
	}

	/*function save()
	{
		if ($this->app()->getFormData("pt") != "" && $this->app()->getFormData("pn") != "" && $this->app()->getFormData("key") == $this->getkey(null)) {
			file_put_contents("save.log", $this->app()->getFormData("pn").":\n".$this->app()->getFormData("pt"));
			$this->dataDS->setData($this->app()->getFormData("pn"), $this->app()->getFormData("pt"));
			return "OK";
		}
		return "";
	}*/

	/** Load page data, may be overriden in your module */
	protected function loadPage()
	{
		$this->wikiPage = new QEntity($this->ds()->getEntity($this->page()->path()));
		if ($this->wikiPage === null) die("404 Not Found");
		$this->pageTitle = $this->wikiPage->get("heading") != "" ? $this->wikiPage->get("heading") : $this->wikiPage->value();
		if ($this->pageTitle !== null && mb_strpos($this->pageTitle, "/") > 0) $this->pageTitle = mb_substr($this->pageTitle, mb_strrpos($this->pageTitle, "/") + 1);
		$this->pagePath = $this->wikiPage->value();
	}

	function edit(Dict $args)
	{
		if (!$this->editable) return "";

		if ($this->page()->path() == "edit") $args->set("id", 1);
		else $this->page()->set("path", substr($this->page()->path(), 0, -5));
		$this->loadPage();

		/*if ($this->app()->getFormData("token") != "asdf") {
			header("HTTP/1.1 401 Unauthorized");
			die("HTTP/1.1 401 Unauthorized");
		}*/
		if ($this->page()->getFormData("edit-pagename") != "") $this->dataDS->setData($this->pagePath, $this->page()->getFormData("edit-pagetext"));
		//if ($this->app()->getFormData("edit-pagename") != "") $this->dataDS->setData($this->app()->getFormData("edit-pagename"), $this->app()->getFormData("edit-pagetext"));
		if ($this->page()->getFormData("edit-delbut") != "") {
			$this->dataDS->delData($this->page()->getFormData("edit-pagename"));
			$this->page()->set("location", $this->page()->get("pathFull").$this->page()->username()."/".$this->wiki."/".$this->wiki."/");
			die;
		}

		try {
			$data = $this->dataDS->getData($this->pagePath)[0]["dv"]; // Has to be string, it's filesystem ds!
			if ($data === null) $data = "";
		} catch (\Exception $e) {
			$data = "";
		}

		$form = new QForm("edit");
		$form->add((new QFormControl(QFormControlType::button, "viewbut", null, "View", 10))->action("view"));
		$form->add(new QFormControl(QFormControlType::text, "pagename", null, htmlspecialchars($this->pageTitle), 10));
		$form->add(new QFormControl(QFormControlType::button, "savebut", null, "Save", 10));
		if (true) $form->add(new QFormControl(QFormControlType::button, "syncbut", null, "Sync", 10));
		$form->add((new QFormControl(QFormControlType::button, "delbut", null, "Delete", 10))->action("delpage"));
		$form->add(new QFormControl(QFormControlType::wikitext, "pagetext", "", trim($data)."\n", 100));

		return $this->QPage($args, $form->convert());
	}

	function pagetext()
	{
		return trim($this->dataDS->getData($this->page()->get("pagename"))[0]["dv"])."\n";
	}


	public function xedit(Dict $args)
	{
		if ($this->stage() == QModuleStage::prod) return ""; // Can't edit on PROD, it's unsafe

		if ($this->page()->path() == "edit") $this->page()->set("id", 1);
		else $this->page()->set("path", substr($this->page()->path(), 0, -5));
		$this->loadPage();

		if ($this->page()->getFormData("edit-savebut") != "") $this->dataDS->setData($this->pagePath, $this->page()->getFormData("edit-pagetext"));
		else if ($this->page()->getFormData("closebut") != "") return $this->main($args);
		else if ($this->page()->envDS("file", "imgfile") != "") {
			$this->dataDS->setContent("imgfile");
			die($this->page()->envDS("file", "imgfile"));
		} else if ($this->page()->getFormData("relbut") != "") $this->ds()->addRel($this->page()->get("id"), $this->page()->getFormData("relpage"));
		try {
			$data = $this->dataDS->getData($this->pagePath)[0]["dv"]; // Has to be string, it's filesystem ds!
		} catch (\Exception $e) {
			$data = "";
		}

		if ($this->page()->ds()->hasFeature("getrela")) {
			$relList = new QList("articles");
			$relList->rows($this->ds()->getEntityRelations($this->wikiPage->id()));
		}

		/*$EditForm = new QForm("edit");
		$EditForm->Add(new QForm\QFormControl("textbox"));
		$EditForm->Add(new QForm\QFormControl("button"));
		$EditForm->ConvertTo($this->outputFormat);*/

		$form = new QForm("edit");
		$form->add((new QFormControl(QFormControlType::button, "viewbut", null, "View", 10))->action("view"));
		$form->add(new QFormControl(QFormControlType::text, "pagename", null, htmlspecialchars($this->pageTitle), 10));
		$form->add(new QFormControl(QFormControlType::button, "savebut", null, "Save", 10));
		if (true) $form->add(new QFormControl(QFormControlType::button, "syncbut", null, "Sync", 10));
		$form->add((new QFormControl(QFormControlType::button, "delbut", null, "Delete", 10))->action("delpage"));
		$form->add(new QFormControl(QFormControlType::wikitext, "pagetext", "", trim(htmlspecialchars($data))."\n", 100));
		return $this->QPage($args, $form->convert());

		return $this->QPage("<form method=\"post\" action=\"\" class=\"pageEdit\">
<div id=\"toolbar\"><input type=\"button\" value=\"View\" name=\"closebut\" onclick=\"location.href='".mb_substr(str_replace("//", "/", $this->app()->get("path").$this->app()->get("pathRel")), 0, -4)."';\"><input type=\"text\" value=\"".htmlspecialchars($this->pageTitle)."\" name=\"pagename\"><input type=\"submit\" value=\"Save\" name=\"text_savebut\" id=\"text_savebut\"><input type=\"submit\" disabled value=\"Delete\" name=\"delbut\" style=\"float:right;\"></div>
<textarea autofocus lang=\"cs\" id=\"text\" name=\"pagetext\" style=\"/*width:620px;*/width:100%;height:700px;margin:0;padding:0 5px;font-size:.85em;\">".htmlspecialchars($data)."</textarea>
</form>".(isset($relList) ? $relList->convert() : "")."
<form method=\"post\" action=\"\" style=\"width:300px;float:right;clear:none;margin-left:0;margin-top:0;\">
	<h3>Nový obrázek</h3>
	<div id=\"dropzone\" style=\"padding:20px 0;width:275px;float:left;text-align:center;border:1px dotted grey;\">Drag & drop your file here...</div>
</form>
<form method=\"post\" action=\"\" style=\"width:300px;float:right;clear:none;margin-left:0;margin-top:0;\">
	<h3>Související články</h3>
	<input type=\"text\" name=\"relpage\" style=\"width:200px;\" placeholder=\"Relativní URL strany\"><input type=\"submit\" name=\"relbut\" value=\"Vložit\" class=\"btn\">
</form>
    <script type=\"text/javascript\">
        function sendFile(file) {
            var uri = \"".$this->app()->get("path").$this->page()->path()."\" + (j.indexOf(\"?\") > 0 ? \"&\" : \"?\") + \"xhr=\" + new Date().getTime();
            var xhr = new XMLHttpRequest();
            var fd = new FormData();

            xhr.open(\"POST\", uri, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
		            var dropzone = document.getElementById(\"dropzone\");
		            dropzone.innerHTML = xhr.responseText;
                }
            };
            fd.append('imgfile', file);
            xhr.send(fd);
        }

        window.onload = function() {
			if (e(\"text\") != null) {
				e(\"text\").onkeydown = function(evt){return wikiTextKeyDown(this,evt);};
			}
            var dropzone = document.getElementById(\"dropzone\");
            dropzone.ondragover = dropzone.ondragenter = function(event) {
            	this.style.borderStyle = 'solid';
                event.stopPropagation();
                event.preventDefault();
            };

            dropzone.ondragleave = function(event) {
            	this.style.borderStyle = 'dotted';
            };

            dropzone.ondrop = function(event) {
                event.stopPropagation();
                event.preventDefault();

                var filesArray = event.dataTransfer.files;
                for (var i=0; i<filesArray.length; i++) {
                    sendFile(filesArray[i]);
                }
            };
        }
    </script>
");
	}

	/*function rightCol()
	{
		$rightCol = new QView("rightcol");
		$rightCol->add($this->banner());
		if ($this->page !== null && $this->app()->ds()->hasFeature("getrela")) $rightCol->add($this->related($this->page->get("title_cs", "p")));
		return $rightCol->convert();
	}

	function banner()
	{
		return "<img src=\"".$this->app()->get("pathContent")."ba/banner300x250.png\">";
	}

	function related()
	{
		$list = new QList("articles");
		$list->rows($this->ds->getEntityRelations($this->page->id()));
		$list->heading("Související články");
		return $list->convert("articles");
	}*/

	function footer(Dict $args)
	{
		$footer = new QView("footer");
		$footer->add("Copyright &copy; ".$this->page()->text()." ".date("Y").". All Right Reserved. <span>Powered by <a href=\"https://www.qetrix.com/\" onclick=\"return !window.open(this);\">QetriX</a>.</span>");
		return $footer->convert();
	}
}
