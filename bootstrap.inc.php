<?php

$pwd = dirname(__FILE__).'/';

// config.default.php sets the default config values.
require_once($pwd.'config.default.php');

// config.php sets the local config values
require_once($pwd'config.php');

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

// cache_downloaded.class.php provides the capacity to generate a
// local version of the website either as backup or for CSS/Javascript
// testing
require_once($cls.'cache_downloaded.class.php');

// config_db.php provides the database connection settings.
require_once($pwd.'config_db.php');


