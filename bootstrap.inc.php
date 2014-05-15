<?php

// As this script can potentially consume all the memory resources of
// the server, we don't want it run via a web interface.
if( isset($_SERVER['HTTP_HOST']) ) exit;


$pwd = dirname(__FILE__).'/';

// config.default.php sets the default config values.
require_once($pwd.'config.default.php');

// config.php sets the local config values
require_once($pwd.'config.php');

// curl_get_simple.class.php handles the basic curl stuff
require_once($cls.'curl_get/curl_get_simple.class.php');

// curl_get_cache.clsss.php extends curl_get_simple to provide extra
// functionality for the cache warmer
require_once($cls.'curl_get_cache.class.php');

// db_abstract.class.php provides basic functionality for DB
// connection
require_once($cls.'db_class/db_abstract.class.php');

// db_mysql.class.php extends db_abstract.class.php implementing
// functionality specific to MySQL
require_once($cls.'db_class/db_mysql.class.php');

// cache_warm.class.php does most of the heavy lifting or the warming
// process
require_once($cls.'cache_warm.class.php');


