<?php

require_once('bootstrap.inc.php');

// variables come from bootstrap.inc.php > config_db.php
$db = new db_mysql( array( 'host' => $db_host , 'username' => $db_user , 'password' => $db_pass , 'database' => $db_name ) );
$curl = new curl_get_cache();
$warmer = new cache_warm_check_urls( $db , $curl );

// $source_list is defined in config.php which is called from 
// bootstrap.inc.php
$warmer->update_url_list( $source_list , $priority_sites );
$warmer->check_new_urls();
