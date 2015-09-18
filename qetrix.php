<?php namespace com\qetrix;

/* Copyright (c) 2015 QetriX. Licensed under MIT License, see /LICENSE.txt file.
 * Initial/Startup/Index file, called by e.g. .htaccess on Apache HTTP Server with mod_rewrite
 */

//ini_set("display_errors", 1);ini_set("display_startup_errors", 1);error_reporting(-1);

use com\qetrix\libs\Util;

require __DIR__."/libs/qapp.php";

// Create QetriX App Instance
$app = libs\QApp::getInstance();

// Read path from GET variable
$path = $app->envDS()->get("get", "_p");

// Parse path
$modVars = $app->getModule($path);

// Call QetriX App Module
$modOutput = $app->loadModule($modVars); // Decode path in GET variable "_p", load module and call func. Then return output

// Send Module output into Environment DataStore
$app->envDS()->output($modOutput);
