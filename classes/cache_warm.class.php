<?php

class cache_warm
{
	private $db = null;
	private $curl = null;
	private $GMT_offset = 0;
	private $get_urls_count = 100;


	public function __construct( right_db $db , curl_get_cache $curl )
	{
		$this->db = $db;
		$this->db->cache_normalised();
		$this->curl = $curl;

		$serverOffset = new DateTime( 'now' , new DateTimeZone( date_default_timezone_get() ) );
		$this->GMT_offset = $serverOffset->getOffset();
	}

	public function update_url_list( $source_url , $check_new = false )
	{
		if( !$this->curl->valid_url($source_url) )
		{
			// throw
			return false;
		}
		$url_list = explode("\n",$this->curl->get_content($source_url));

		
		for( $a = 0 ; $a < count($url_list) ; $a += 1 )
		{
			$url_list[$a] = trim($url_list[$a]);
			if( strlen($url_list[$a]) > 11 )
			{
				if( substr($url_list[$a],0,5) == 'https' )
				{
					$url_list[$a] = substr($url_list[$a],8);
				}
				else
				{
					$url_list[$a] = substr($url_list[$a],7);
				}
				$depth = substr_count($url_list[$a],'/');
				$e_lru = $this->db->escape(strrev($url_list[$a]));
				$e_lr = $this->db->escape(substr(strrev($url_list[$a]),0,2));
				$sql = 'SELECT `url_id` FROM `urls` WHERE `url_url_sub` = "'.$e_lr.'" AND `url_url` = "'.$e_lru.'"';
				$result = $this->db->fetch_1($sql);
				if( $result === null )
				{
					$sql = '
INSERT INTO `urls`
(
	 `url_url`
	,`url_url_sub`
	,`url_depth`
)
VALUES
(
	 "'.$e_lru.'"
	,"'.$e_lr.'"
	,'.$depth.'
)';
					$this->db->query($sql);

				}
			}
		}
		if( $check_new === true )
		{
			$this->check_new_urls();
		}
	}

	public function check_new_urls()
	{
		$sql = '
SELECT	 `url_id` AS `id`
	,REVERSE(`url_url`) AS `url`
FROM	`urls`
WHERE	`url__url_status_id` = '.$this->db->get_cached_id('new','_url_status').'
ORDER BY `url_depth` ASC';
		$url_list = $this->db->fetch_($sql);
		if( $url_list !== null )
		{
			$b = 0;
			$url_good_sql_start = $url_good_sql = '
UPDATE `urls`
SET `url__url_status_id` = '.$this->db->get_cached_id('good','_url_status').'
WHERE `url_id` IN ( ';
			$url_bad_sql_start = $url_bad_sql = '
UPDATE `urls`
SET `url__url_status_id` = '.$this->db->get_cached_id('bad','_url_status').'
WHERE `url_id` IN ( ';

			$cache_sql_start = $cache_sql = '
INSERT INTO `url_by_protocol`
(
	 `url_by_protocol__url_id`
	,`url_by_protocol_https`
	,`url_by_protocol_ok`
	,`url_by_protocol_is_cached`
	,`url_by_protocol_cache_expires`
)
VALUES
 ';
 			$cache_sep = $url_good_sep = $url_bad_sep = '';
			for( $a = 0 ; $a < count($url_list) ; $a += 1 )
			{
				// check the HTTP version of the url
				$http = $this->curl->check_url("http://{$url_list[$a]['url']}");//debug($url_list[$a]);
				$url_ok = false;
				if( $http['is_valid'] == 1 )
				{
					$b += 1;
					$cache_sql .= "$cache_sep( {$url_list[$a]['id']} , 0 , {$http['is_valid']} , {$http['is_cached']} , '{$http['date']}' )";
					$cache_sep = "\n,";
					$url_ok = true;
				}

				// check the https version of the URL
				$https = $this->curl->check_url("http://{$url_list[$a]['url']}");
				if( $https['is_valid'] == 1 )
				{
					$b += 1;
					$cache_sql .= "$cache_sep( {$url_list[$a]['id']} , 1 , {$https['is_valid']} , {$https['is_cached']} , '{$https['date']}' )";
					$cache_sep = "\n,";
					$url_ok = true;
				}

				if( $url_ok )
				{
					$url_good_sql .= "$url_good_sep{$url_list[$a]['id']}";
					$url_good_sep = ' , ';
				}
				else
				{
					$url_bad_sql .= "$url_bad_sep{$url_list[$a]['id']}";
					$url_bad_sep = ' , ';
				}

				if( $b > 50 )
				{
					// We've looked at 50 URLs. Lets store the info in the DB now.

					$this->db->query($cache_sql);
					$cache_sql = $cache_sql_start;
					$cache_sep = '';
					if( $url_good_sep != '' )
					{
						$this->db->query($url_good_sql.' )');
						$url_good_sql = $url_good_sql_start;
						$url_good_sep = '';
					}
					if( $url_bad_sep != '' )
					{
						$this->db->query($url_bad_sql.' )');
						$url_bad_sql = $url_bad_sql_start;
						$url_bad_sep = '';
					}
					$b = 0;
				}
			}
			if( $cache_sep != '' )
			{
				$this->db->query($cache_sql);
			}
			if( $url_good_sep != '' )
			{
				$this->db->query($url_good_sql.' )');
			}
			if( $url_bad_sep != '' )
			{
				$this->db->query($url_bad_sql.' )');
			}
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
		$now = '"'.$this->db->escape(gmdate('Y-m-d H:i:s')).'"';
		$now_time = strtotime(gmdate('Y-m-d H:i:s'));

		$sql = '
SELECT	 `urls`.`url_id` AS `id`
	,REVERSE(`urls`.`url_url`) AS `url`
	,`url_by_protocol`.`url_by_protocol_https` AS `https`
FROM	`urls`
	,`url_by_protocol`
WHERE	`urls`.`url_id` = `url_by_protocol`.`url_by_protocol__url_id`
AND	`urls`.`url__url_status_id` = '.$this->db->get_cached_id('good','_url_status').'
AND	`url_by_protocol`.`url_by_protocol_ok` = 1
AND	`url_by_protocol`.`url_by_protocol_is_cached` = 1
AND	`url_by_protocol`.`url_by_protocol_cache_expires` < '.$now.'
ORDER BY `url_by_protocol`.`url_by_protocol_cache_expires` DESC
	,`urls`.`url_depth` ASC
LIMIT 0,'.$this->get_urls_count;
		$result = $this->db->fetch_($sql);
		if( $result !== null )
		{
			$output = array();
			for( $a = 0 ; $a < count($result) ; $a += 1 )
			{
				if( $result[$a]['https'] == 1 )
				{
					$result[$a]['url'] = "https://{$result[$a]['url']}";
				}
				else
				{
					$result[$a]['url'] = "http://{$result[$a]['url']}";
				}
				$output[] = $result[$a];
			}
			return $output;
		}
		else
		{
			$sql = '
SELECT	`url_by_protocol_cache_expires` AS `expires`
FROM	`url_by_protocol`
WHERE	`url_by_protocol_ok` = 1
AND	`url_by_protocol_is_cached` = 1
AND	`url_by_protocol_cache_expires` >= '.$now.'
ORDER BY `url_by_protocol_cache_expires` DESC
LIMIT 0,1';
			$next_expires = $this->db->fetch_1($sql);
			if( $next_expires !== null )
			{
				return strtotime($next_expires) - $now_time;
			}
		}
		return false;
	}

	public function get_uncached_urls($start = 0 )
	{
		if( !is_int($start) || $start < 100 )
		{
			$start = 0;
		}

		
		$sql = '
SELECT 	 DISTINCT `urls`.`url_id` AS `id`
	,REVERSE(`urls`.`url_url`) AS `url`
FROM	 `urls`
	,`url_by_protocol`
WHERE	`urls`.`url_id` = `url_by_protocol`.`url_by_protocol__url_id`
AND	`url_by_protocol`.`url_by_protocol_ok` = 1
AND	`url_by_protocol`.`url_by_protocol_is_cached` = 0';
		
		$url_list = $this->db->fetch_($sql);
		if( $url_list != null )
		{
			return $url_list;
		}
		return false;
	}

	public function warm_url( $url_info )
	{
		if( !is_array($url_info) || !isset($url_info['url']) || !isset($url_info['sub']) || !isset($url_info['http']) || !isset($url_info['https']) )
		{
			// throw
		}
		$downloaded = $this->curl->check_url( $url_info['url'] , false );//debug($url_info,$this->curl->httpobject->get_headers_array());
		$status = 'good';
		if( $downloaded['is_valid'] )
		{
			$output = true;
		}
		else
		{
			$output = false;
			if( $url_info['https'] == 1 )
			{
				$protocol = 0;
			}
			else
			{
				$protocol = 1;
			}
			$sql = "
SELECT	COUNT(*) AS `count`
FROM	`url_by_protocol`
WHERE	`url_by_protocol_https` = $protocol
AND	`url_by_protocol_ok` = 0";
			if( $this->db->fetch_1($sql) == 1 )
			{
				$status = 'bad';
			}
		}
		$sql = "
UPDATE	 `urls`
	,`url_by_protocol`
SET	 `urls`.`url__url_status_id` = ".$this->db->get_cached_id($status,'_url_status')."
	,`url_by_protocol_ok` = {$downloaded['is_valid']}
	,`url_by_protocol_is_cached` = {$downloaded['is_cached']}";
		if( $downloaded['expires'] !== null )
		{
			$sql .= "\n\t,`url_by_protocol_cache_expires` = '".$this->db->escape(date('Y-m-d H:i:s',$downloaded['expires']))."'";
		}
		$sql .= "
WHERE	`urls`.`url_id` = {$url_info['id']}
AND	`urls`.`url_id` = `url_by_protocol`.`url_by_protocol__url_id`
AND	`url_by_protocol`.`url_by_protocol_https` = {$url_info['https']}";

		$this->db->query($sql);

		return $output;
	}

	public function set_get_urls_count( $count )
	{
		if( is_int($count) && $count > 0 )
		{
			$this->get_urls_count = $count;
			return true;
		}
		return false;
	}
}
