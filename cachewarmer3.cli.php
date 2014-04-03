<?php

// ==================================================================
// START: debug include

if(!function_exists('debug'))
{
	if(isset($_SERVER['HTTP_HOST'])){ $path = $_SERVER['HTTP_HOST']; $pwd = dirname($_SERVER['SCRIPT_FILENAME']).'/'; }
	else { $path = $_SERVER['USER']; $pwd = $_SERVER['PWD'].'/'; };
	if( substr_compare( $path , '192.168.' , 0 , 8 ) == 0 ) { $path = 'localhost'; }
	switch($path)
	{
		case '192.168.18.128':	// work laptop (debian)
		case 'antechinus':	// work laptop (debian)
		case 'localhost':	// home laptop
		case 'evan':		// home laptop
		case 'wombat':	$root = '/var/www/';	$inc = $root.'includes/'; $classes = $cls = $root.'classes/'; break; // home laptop

		case 'burrawangcoop.net.au':	// DreamHost
		case 'adra.net.au':		// DreamHost
		case 'canc.org.au':		// DreamHost
		case 'ewills':	$root = '/home/ewills/evan/'; $inc = $root.'includes/'; $classes = $cls = $root.'classes/'; break; // DreamHost

		case 'apps.acu.edu.au':		// ACU
		case 'testapps.acu.edu.au':	// ACU
		case 'dev1.acu.edu.au':		// ACU
		case 'blogs.acu.edu.au':	// ACU
		case 'studentblogs.acu.edu.au':	// ACU
		case 'dev-blogs.acu.edu.au':	// ACU
		case 'evanw':	$root = '/home/evanw/';	$inc = $root.'includes/'; $classes = $cls = $root.'classes/'; break; // ACU

		case 'webapps.acu.edu.au':	   // ACU
		case 'panvpuwebapps01.acu.edu.au': // ACU
		case 'test-webapps.acu.edu.au':	   // ACU
		case 'panvtuwebapps01.acu.edu.au': // ACU
		case 'dev-webapps.acu.edu.au':	   // ACU
		case 'panvduwebapps01.acu.edu.au': // ACU
		case 'evwills':
			if( isset($_SERVER['HOSTNAME']) && $_SERVER['HOSTNAME'] = 'panvtuwebapps01.acu.edu.au' ) {
				$root = '/home/evwills/'; $inc = $root.'includes/'; $classes = $cls = $root.'classes/'; break; // ACU
			} else {
				$root = '/var/www/html/mini-apps/'; $inc = $root.'includes_ev/'; $classes = $cls = $root.'classes_ev/'; break; // ACU
			}
	};

	set_include_path( get_include_path().PATH_SEPARATOR.$inc.PATH_SEPARATOR.$cls.PATH_SEPARATOR.$pwd);

	if(file_exists($inc.'debug.inc.php'))
	{
		if(!file_exists($pwd.'debug.info') && is_writable($pwd) && file_exists($inc.'template.debug.info'))
		{ copy( $inc.'template.debug.info' , $pwd.'debug.info' ); };
		include($inc.'debug.inc.php');
	}
	else { function debug(){}; };

	class emergency_log { public function write( $msg , $level = 0 , $die = false ){ echo $msg; if( $die === true ) { exit; } } }
};

// END: debug include
// ==================================================================

require_once($cls.'curl_get/curl_get_simple.class.php');
require_once($cls.'db_class/db_abstract.class.php');
require_once($cls.'db_class/db_mysql.class.php');
require_once('classes/curl_get_cache.class.php');
require_once('config_db.php');

$source_list = 'http://www.acu.edu.au/admin/warm_up/list_all/';
#$source_list = 'http://www.acu.edu.au/admin/warm_up/list_all/_nocache';

$db = new db_mysql( array( 'host' => $host , 'username' => $db_user , 'password' => $db_pass , 'database' => $db_name ) );


$serverOffset = new DateTime( 'now' , new DateTimeZone( date_default_timezone_get() ) );
$GMT_offset = $serverOffset->getOffset();
unset($serverOffset);

$curl = new curl_get_cache();
$mem = round( ( memory_get_usage() / 1024 ) / 1024 ,3 );
debug( $mem );

while( $mem < 50 )
{
	$now = '"'.$db->escape(gmdate('Y-m-d H:i:s')).'"';
	$now_time = gmmktime();
	$sql = '
SELECT	 `url_id` AS `id`
	,REVERSE(`url_url`) AS `url`
	,`url_url_sub` AS `sub`
	,`url_http_cached` AS `http_cached`
	,`url_http_cache_expires` AS `http_expires`
	,`url_https_cached` AS `https_cached`
	,`url_https_cache_expires` AS `https_expires`
FROM	`urls`
WHERE
(
	`url_http` = 1
AND	`url_http_cached` = 1
AND	`url_http_cache_expires` < '.$now.'
)
OR
(
	`url_https` = 1
AND	`url_https_cached` = 1
AND	`url_https_cache_expires` < '.$now.'
)
LIMIT 0,100
';
	$result = $this->fetch($sql);
	if( $result !== null )
	{
		for( $a = 0 ; $a < count($result) ; $a += 1 )
		{
			$sql = '';
			$sep = ' ';
			if( $result[$a]['http_cached'] && strtotime($result[$a]['http']) < $now_time )
			{
				// Warm the cache for the HTTP version of the URL
				$http = $curl->warm_url( 'http://'.$result['url'] );

				if( strtotime($http['expires']) > strtotime($result[$a]['http_expires']) )
				{
					$sql .= "\t$sep`url_http` = {$http['is_valid']}\n\t,`url_http_cached` = {$http['is_cached']}\n\t,`url_http_cache_expires` = '"
					     .$db->escape(date('Y-m-d H:i:s',$http['expires']))."'";
					$sep = ',';
				}
				else
				{
					debug($result[$a],$http,'Cache was not warmed for HTTPS');
				}
				unset($http);
			}

			if( $result[$a]['https_cached'] && strtotime($result[$a]['http_expires']) < $now_time )
			{
				// Warm the cache for the HTTPS version of the URL
				$https = $curl->warm_url( 'https://'.$result['url'] );
				
				if( strtotime($https['expires']) > strtotime($result[$a]['https_expires']) )
				{
					$sql .= "\t$sep`url_https` = {$https['is_valid']}\n\t,`url_https_cached` = {$https['is_cached']}\n\t,`url_https_cache_expires` = '"
					     .$db->escape(date('Y-m-d H:i:s',$https['expires']))."'";
				}
				else
				{
					debug($result[$a],$https,'Cache was not warmed for HTTPS');
				}
				unset($https);
			}

			if( $sql != '' )
			{
				$sql = "\nUPDATE\t`urls`\nSET{$sql}\nWHERE\t`url_id` = {$result[$a]['id']}";
				$db->query($sql);
			}
		}
	}
	else
	{
		$sql = '
SELECT	 `url_http_cache_expires` AS `http_expires`
	,`url_https_cache_expires` AS `https_expires`
FROM	`urls`
WHERE
(
	`url_http` = 1
AND	`url_http_cached` = 1
AND	`url_http_cache_expires` >= '.$now.'
)
OR
(
	`url_https` = 1
AND	`url_https_cached` = 1
AND	`url_https_cache_expires` >= '.$now.'
)
ORDER BY `http_expires` DESC
,ORDER BY `https_expires` DESC
LIMIT 0,1
';
	}
}


