<?php
declare(strict_types = 1);
namespace com\qetrix;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.05.17 | Initial/Startup/Index file, called by webserver's rewrite engine
 */

//ini_set("display_errors", 1);ini_set("display_startup_errors", 1);error_reporting(-1);

use com\qetrix\libs\QPage;
use com\qetrix\libs\Util;

require __DIR__."/libs/qpage.php";

// Create QetriX App Instance
$page = QPage::getInstance();

// Parse path
$modVars = $page->parsePath();

// Call QetriX App Module
$modOutput = $page->loadModule($modVars); // Decode path in GET variable "_p", load module and call func. Then return output

// Send Module output into Environment DataStore
$page->set("output", $modOutput);
