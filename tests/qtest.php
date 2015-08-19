<?php namespace com\qetrix\tests;

/* Copyright (c) 2015 QetriX. Licensed under MIT License, see /LICENSE.txt file.
 * QetriX Tester Class
 */

use com\qetrix\libs\Util;

include_once __DIR__."/../libs/util.php";

class QTest
{
	protected $startTime;
	protected $out = "";
	private $testCount = 0;

	// Create new test suite
	function __construct($name)
	{
		$this->startTime = microtime(true);
		$this->out .= "== Testing ".$name." ==\n";
		$this->out .= "Start time	".date("c")."\n";
		$this->out .= "\n== Results ==\n";
	}

	// Add new test case
	function add($name)
	{
		$this->testCount++;
		$this->out .= ($this->testCount)."	".$name."	";
	}

	// Check test case result
	function check($cond)
	{
		if ($cond) {
			$this->out .= "OK\n";
		} else {
			http_response_code(500);
			$this->out .= "Failed\n";
		}
	}

	// Print test case summary
	function summary()
	{
		$this->out .= "\n=== Summary ===\n";
		$this->out .= "Finish time	".date("c")."\n";
		$this->out .= "Total time	".round(microtime(true) - $this->startTime, 4)."\n";
		$this->out .= "Final mem	".(memory_get_usage())."\n";
		return $this->out;
	}

	// Finish test case
	function finish()
	{
		$this->out .= "Code	Description	Result\n";
		ob_flush();
		if (!headers_sent()) {
			header("Content-Type: text/plain");
		}
		echo $this->out;
	}
}
