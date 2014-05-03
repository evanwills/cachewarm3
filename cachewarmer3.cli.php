<?php

require_once('bootstrap.inc.php');


$db = new db_mysql( array( 'host' => $host , 'username' => $db_user , 'password' => $db_pass , 'database' => $db_name ) );
$curl = new curl_get_cache();

$warm = new cache_warm( $db , $curl , $cache_local );

if( $_SERVER['argc'] > 1 && $_SERVER['argv'][1] == 'cache_local' && isset($local_cache_path) )
{
	$cache_local = new cache_locally( $curl , $local_cache_path );
	$warm->set_cache_locally($cache_local);
}


$mem = round( ( memory_get_usage() / 1024 ) / 1024 ,3 );

while( $mem < 50 )
{
	$urls_list = get_urls_to_warm();
	if( $urls_list === false )
	{
		exit;
	}
	elseif( is_int($urls_list) )
	{
		sleep($urls_list);
	}
	else
	{
		for( $a = 0 ; $a < count($urls_list) ; $a += 1 )
		{
			if( $mem > 50 )
			{
				exit;
			}
			$warm->warm_url( $result[$a] );
			$mem = round( ( memory_get_usage() / 1024 ) / 1024 ,3 );
		}
	}
	$mem = round( ( memory_get_usage() / 1024 ) / 1024 ,3 );
}


