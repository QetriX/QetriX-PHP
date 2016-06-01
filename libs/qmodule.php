<?php declare(strict_types = 1);
namespace com\qetrix\libs;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.05.16 | QetriX Module PHP class
 */

use com\qetrix\libs\components\QList;
use com\qetrix\libs\components\QView;
use com\qetrix\libs\QPage;
use com\qetrix\libs\QEntity;
use com\qetrix\libs\Util;

class QModule
{
	protected $_ds; // Primary DataStore
	protected $varDS; // Variable files DataStore (logs, temps)
	protected $dataDS; // Data DataStore
	protected $contentDS; // Content DataStore (for PHP always FileSystem)
	/** @var  QModuleStage $_stage */
	public $_stage;
	/** @var  QPage $_page */
	protected $_page;
	/** @var  QEntity $entity */
	protected $entity;
	protected $heading = "";

	/** PHP constructor */
	function __construct($page)
	{
		$this->QModule($page);
	}

	/** Class Constructor */
	function QModule(QPage $page)
	{
		$this->_page = $page;
		$this->_ds = $this->page()->ds();
		$this->_stage = $this->page()->stage();
	}

	/** @return DataStore */
	public function ds()
	{
		return $this->_ds;
	}

	/** @return QPage */
	public function page()
	{
		return $this->_page;
	}

	protected function QPage(Dict $args, $content, $heading = "", $style = "")
	{
		$page = new QView();
		$page->heading($heading == "" ? $this->heading : "");
		$page->add($content);
		return $page->convert("page");
	}

	/** @return QModuleStage */
	public function stage()
	{
		return $this->_stage;
	}

	public function trl_ac()
	{
		$list = new QList("trl_ac");
		$list->add($this->ds()->trl_ac($this->page()->getFormData("value"), $this->page()->get("id")));
		return $list->convert();
	}
}
