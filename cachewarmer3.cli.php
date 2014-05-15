<?php

require_once('bootstrap.inc.php');

// throttle.class.php provides the capacity to limit the rate at
// which URLs are warmed
require_once($cls.'throttle.class.php');

// cache_downloaded.class.php provides the capacity to generate a
// local version of the website either as backup or for CSS/Javascript
// testing
//require_once($cls.'cache_downloaded.class.php');




$instance = isset($_SERVER['argv'][1])?$_SERVER['argv'][1]:0;

$db = new db_mysql( array( 'host' => $host , 'username' => $db_user , 'password' => $db_pass , 'database' => $db_name ) );
$curl = new curl_get_cache();

$warm = new cache_warm( $db , $curl , $instance );
$rate = new throttle($throttle_rate);

if( $_SERVER['argc'] > 1 && $_SERVER['argv'][1] == 'cache_local' && isset($local_cache_path) )
{
	$cache_local = new cache_locally( $curl , $local_cache_path );
	$warm->set_cache_locally($cache_local);
}


$memory_used = round( ( memory_get_usage() / 1024 ) / 1024 ,3 );

while( $memory_used < $memory_limit )
{
	$urls_list = $warm->get_urls_to_warm();
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
			if( $memory_used > $memory_limit )
			{
				exit;
			}
			$warm->warm_url( $urls_list[$a] );
			
			// By default throttle does nothing. If you specify a throttle rate
			// of greater than zero, then the warming is throttled to match the
			// number of URLs per second you specified in $throttle_rate.
			$rate->throttle();

			// Update the amount of memory the script is using.
			$memory_used = round( ( memory_get_usage() / 1024 ) / 1024 ,3 );
		}
	}
	$memory_used = round( ( memory_get_usage() / 1024 ) / 1024 ,3 );
}


