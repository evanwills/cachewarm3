<?php

require_once('bootstrap.inc.php');

// gapi.class.php manages extracting analytics data out of Google
// Analytics
require_once($cls.'gapi/gapi.class.php');


// variables come from bootstrap.inc.php > config_db.php
$db = new db_mysql( array( 'host' => $host , 'username' => $db_user , 'password' => $db_pass , 'database' => $db_name ) );
$curl = new curl_get_cache();
$warmer = new cache_warm_ga_stats( $db , $curl );

$ga = new gapi( $ga_email , $ga_password );

// $source_list is defined in config.php which is called from 
// bootstrap.inc.php
$warmer->get_ga_rankings( $ga , $ga_profile_ID );
$warmer->persist_ga_rankings();
