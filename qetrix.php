<?php
namespace com\qetrix;

/* Copyright (c) QetriX.com. Licensed under MIT License, see /LICENSE.txt file.
 * 16.01.04 | Initial/Startup/Index file, called by webserver rewrite engine
 */

//ini_set("display_errors", 1);ini_set("display_startup_errors", 1);error_reporting(-1);

use com\qetrix\libs\QApp;
use com\qetrix\libs\Util;

require __DIR__."/libs/qapp.php";

// Create QetriX App Instance
$app = QApp::getInstance();

// Read path from GET variable
$path = $app->envDS()->get("get", "_p");

// Parse path
$modVars = $app->parsePath($path);

// Call QetriX App Module
$modOutput = $app->loadModule($modVars); // Decode path in GET variable "_p", load module and call func. Then return output

// Send Module output into Environment DataStore
$app->envDS()->output($modOutput);
