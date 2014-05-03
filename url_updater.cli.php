<?php

require_once('bootstrap.inc.php');

// variables come from bootstrap.inc.php > config_db.php
$db = new db_mysql( array( 'host' => $host , 'username' => $db_user , 'password' => $db_pass , 'database' => $db_name ) );
$curl = new curl_get_cache();
$warmer = new cache_warm( $db , $curl );

// $source_list comes from bootstrap.inc.php > config.php
$warmer->update_url_list($source_list);
