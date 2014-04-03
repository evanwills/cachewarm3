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
// $source_list = 'http://www.acu.edu.au/admin/warm_up/list_all/_nocache';

$db = new db_mysql( array( 'host' => $host , 'username' => $db_user , 'password' => $db_pass , 'database' => $db_name ) );


$serverOffset = new DateTime( 'now' , new DateTimeZone( date_default_timezone_get() ) );
$GMT_offset = $serverOffset->getOffset();
unset($serverOffset);

$curl = new curl_get_cache();
debug( round( ( memory_get_usage() / 1024 ) / 1024 ,3 ));
$url_list = explode("\n",$curl->get_content($source_list)); debug( round( ( memory_get_usage() / 1024 ) / 1024 , 3 ));sleep(3);

for( $a = 0 ; $a < count($url_list) ; $a += 1 )
{
	$url_list[$a] = trim($url_list[$a]);
	if( strlen($url_list[$a]) > 11 )
	{
		$downloaded = $curl->check_url_both($url_list[$a]);
		$e_lru = $db->escape(strrev($downloaded['raw_url']));
		$e_lr = $db->escape(substr(strrev($downloaded['raw_url']),0,2));
		$sql = 'SELECT `url_id` FROM `urls` WHERE `url_url_sub` = "'.$e_lr.'" AND `url_url` = "'.$e_lru.'"';
		$result = $db->fetch_1($sql);
		if( $result === null )
		{
			$sql = '
INSERT INTO `urls`
(
	 `url_url`
	,`url_url_sub`
	,`url_http_ok`
	,`url_http_cached`
	,`url_http_cache_expires`
	,`url_https_ok`
	,`url_https_cached`
	,`url_https_cache_expires`
)
VALUES
(
	 "'.$e_lru.'"
	,"'.$e_lr.'"
	,'.$downloaded['http']['is_valid'].'
	,'.$downloaded['http']['is_cached'].'
	,"'.$db->escape(date('Y-m-d H:i:s',$downloaded['http']['expires'])).'"
	,'.$downloaded['https']['is_valid'].'
	,'.$downloaded['https']['is_cached'].'
	,"'.$db->escape(date('Y-m-d H:i:s',$downloaded['https']['expires'])).'"
)';
		}
		else
		{
			$sql = '
UPDATE `urls`
SET	 `url_http_ok` = '.$downloaded['http']['is_valid'].'
	,`url_http_cached` = '.$downloaded['http']['is_cached'].'
	,`url_http_cache_expires` = "'.$db->escape(date('Y-m-d H:i:s',$downloaded['http']['expires'])).'"
	,`url_https_ok` = '.$downloaded['https']['is_valid'].'
	,`url_https_cached`= '.$downloaded['https']['is_cached'].'
	,`url_https_cache_expires` = "'.$db->escape(date('Y-m-d H:i:s',$downloaded['https']['expires'])).'"
WHERE	`url_id` = '.$result;
		}
		$db->query($sql);
	}
	debug( round(( memory_get_usage() / 1024 ) / 1024 , 3 ));
}
