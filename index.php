<?php
date_default_timezone_set("Europe/Paris");
define("MEMORY_REAL_USAGE", true);
define('INIT_TIME', microtime(true));
define('INIT_MEMORY', memory_get_usage(MEMORY_REAL_USAGE));

/*
// Allow from any origin
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');    // cache for 1 day
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

}*/

require_once(__DIR__."/includes/libs/core/application/class.Singleton.php");
require_once(__DIR__."/includes/libs/core/application/class.Autoload.php");

use core\application\Autoload;
use core\application\Core;

Autoload::$folder = __DIR__;
spl_autoload_register(array(Autoload::getInstance(), "load"));

Core::checkEnvironment();
Core::init();
Core::parseURL();
Core::execute(Core::getController(), Core::getAction(), Core::getTemplate());
Core::endApplication();
