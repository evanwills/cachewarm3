<?php

class cache_warm
{
	private $db = null;
	private $curl = null;
	private $cache_local = false;;
	private $GMT_offset = 0;


	public function __construct( right_db $db , curl_get_cache $curl , cache_downloaded_default $cache_local = null )
	{
		$this->db = $db;
		$this->curl = $curl;
		$this->cache_local = $curl->get_cache_locally_status();

		$serverOffset = new DateTime( 'now' , new DateTimeZone( date_default_timezone_get() ) );
		$this->GMT_offset = $serverOffset->getOffset();
	}

	public function update_url_list( $source_url )
	{
		if( !$this->curl->valid_url($source_url) )
		{
			// throw
			return false;
		}
		debug( round( ( memory_get_usage() / 1024 ) / 1024 , 3 ));
		$url_list = explode("\n",$this->curl->get_content($source_list)); debug( round( ( memory_get_usage() / 1024 ) / 1024 , 3 ));sleep(3);

		
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
	,`url_http_is_cached`
	,`url_http_cache_expires`
	,`url_https_ok`
	,`url_https_is_cached`
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
	}

/**
 * @method get_urls_to_warm() returns an array of URLs that need to
 *	   be warmed. Or, if there are none that are ready to be
 *	   warmed, it returns the number of seconds to wait until the
 *	   next URL is ready to be warmed. If there are no URLs
 *	   waiting to be warmed, it reutrns false
 *
 * @param VOID
 *
 * @return mixed array if there are URLs to be warmed it returns an indexed array of associative arrays:
 *		array [] => array(
 *			 'id' => [X] integer the UID for that URL in
 *			 	 the DB
 *			,'url' => the URL (excluding protocol and
 *				  slashes)
 *			,'sub' => the last to characters of the URL
 *			,'http' => boolean whether or not to warm the
 *				   HTTP version of the URL
 *			,'https' => boolean whether or not to warm
 *				    the HTTPs version of the URL
 *		)
 *		integer If the script should wait because there are
 *			no URLs to be warmed
 *		false if there are no URLs ready or waiting for
 *			warming (script should exit on false)
 */
	public function get_urls_to_warm()
	{
		$now = '"'.$db->escape(gmdate('Y-m-d H:i:s')).'"';
		$now_time = strtotime(gmdate('Y-m-d H:i:s'));

		$sql = '
SELECT	 `url_id` AS `id`
	,REVERSE(`url_url`) AS `url`
	,`url_url_sub` AS `sub`
	,`url_http_is_cached` AS `http_is_cached`
	,`url_http_cache_expires` AS `http_expires`
	,`url_https_is_cached` AS `https_is_cached`
	,`url_https_cache_expires` AS `https_expires`
FROM	`urls`
WHERE
(
		`url_http_ok` = 1
	AND	`url_httpis_is_cached` = 1
	AND	`url_http_cache_expires` < '.$now.'
)
OR
(
		`url_https_ok` = 1
	AND	`url_https_is_cached` = 1
	AND	`url_https_cache_expires` < '.$now.'
)
LIMIT 0,100
';
		$result = $this->db->_fetch($sql);
		if( $result !== null )
		{
			for( $a = 0 ; $a < count($result) ; $a += 1 )
			{
				$result[$a]['http'] = false;
				$result[$a]['https'] = false;
				);
				if( $result[$a]['http_is_cached'] && strtotime($result[$a]['http_cache_expires']) < $now_time )
				{
					$result[$a]['http'] = true;
				}
				if( $result[$a]['https_is_cached'] && strtotime($result[$a]['https_cache_expires']) < $now_time )
				{
					$result[$a]['http'] = true;
				}
				unset($result[$a]['http_is_cached'],$result[$a]['http_cache_expires'],$result[$a]['https_is_cached'],$result[$a]['https_cache_expires']);
			}
			return $result;
		}
		else
		{
			$sql = '
SELECT	`url_http_cache_expires` AS `http_expires`
FROM	`urls`
WHERE	`url_http` = 1
AND	`url_http_is_cached` = 1
AND	`url_http_cache_expires` >= '.$now.'
ORDER BY `http_expires` DESC
LIMIT 0,1
';
			$next_http = $this->db->fetch_1($sql);
			if( $next_http !== null )
			{
				$next_http -= $now_time;
			}

			$sql = '
SELECT	`url_https_cache_expires` AS `https_expires`
FROM	`urls`
WHERE	`url_https` = 1
AND	`url_https_is_cached` = 1
AND	`url_https_cache_expires` >= '.$now.'
ORDER BY `https_expires` DESC
LIMIT 0,1
';
			$next_https = $this->db->fetch_1($sql);
			if( $next_https !== null )
			{
				$next_https -= $now_time;
			}

			if( $next_http > $next_https )
			{
				return $next_https;
			}
			elseif( $next_http === null && $next_https === null )
			{
				return false;
			}
			else
			{
				return $next_http;
			}
		}
	}

	public function warm_url( $url_info )
	{
		if( !is_array($url_info) || !isset($url_info['url']) || !isset($url_info['sub']) || !isset($url_info['http']) || !isset($url_info['https']) )
		{
			// throw
		}
		$headers_only = !$this->cache_locally;
		if( $url_info['http'] === true )
		{
			$http = $curl->warm_url( 'http://'.$url_info['url'] , $headers_only );
			$sql	.= "\t$sep`url_http` = {$http['is_valid']}\n\t,`url_http_is_cached` = {$http['is_cached']}\n\t,`url_http_cache_expires` = '"
				.$this->db->escape(date('Y-m-d H:i:s',$http['expires']))."'";
			$sep = ',';
			if( $http['is_valid'] )
			{
				$headers_only = true;
			}
			unset($http);
		}
		if( $url_info['https'] === true )
		{
			$https = $curl->warm_url( 'https://'.$url_info['url'] , $headers_only );
			$sql	.= "\t$sep`url_http` = {$http['is_valid']}\n\t,`url_http_is_cached` = {$http['is_cached']}\n\t,`url_http_cache_expires` = '"
				.$this->db->escape(date('Y-m-d H:i:s',$http['expires']))."'";
			$sep = ',';
		}
		if( $sql != '' )
		{
			$sql = "\nUPDATE\t`urls`\nSET{$sql}\nWHERE\t`url_id` = {$url_info['id']}";
			$db->query($sql);
			return true;
		}
		return false;
	}

}
