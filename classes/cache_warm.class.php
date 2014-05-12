<?php

class cache_warm
{
	private $db = null;
	private $curl = null;
	private $GMT_offset = 0;
	private $get_urls_count = 100;
	private $limit_start = 0;
	private $_order_by = ';
ORDER BY `urls`.`url_domain_priority` DESC
	,`urls`.`url_depth` ASC
	,`url_by_protocol`.`url_by_protocol_cache_expires` DESC';


	public function __construct( right_db $db , curl_get_cache $curl , $limit_start = 0 , $priority_order = 'domain_depth_expires')
	{
		$this->db = $db;
		$this->db->cache_normalised();
		$this->curl = $curl;

		$serverOffset = new DateTime( 'now' , new DateTimeZone( date_default_timezone_get() ) );
		$this->GMT_offset = $serverOffset->getOffset();

		if( is_int($limit_start) && $limit_start > 0 )
		{
			$this->limit_start = ( $limit_start * $this->get_urls_count );
		}
		$this->set_order_by('domain_depth_expires');
	}

	public function update_url_list( $source_url , $priority_domains = array() )
	{
		if( !$this->curl->valid_url($source_url) )
		{
			// throw
			return false;
		}
		$url_list = explode("\n",$this->curl->get_content($source_url));

		$priority_domains = $this->_make_priority_domains_usable( $priority_domains );

		$unprioritised = count($priority_domains);

		for( $a = 0 ; $a < count($url_list) ; $a += 1 )
		{
			$url_list[$a] = trim($url_list[$a]);
			$url_bits = $this->curl->get_url_parts($url_list[$a]);
			if( $url_bits != false )
			{
				$url = $url_bits['domain'].$url_bits['path'].$url_bits['file'];
				if( $url_bits['protocol'] == 'https' )
				{
					$url_list[$a] = substr($url_list[$a],8);
				}
				else
				{
					$url_list[$a] = substr($url_list[$a],7);
				}
				$priority = false;
				for( $b = 0 ; $b < $unprioritised ; $b += 1 )
				{
					if( substr( $url_list[$a] , 0 , $priority_domains[$b]['chars'] ) == $priority_domains[$b]['url'] )
					{
						$priority = $b;
						if( $priority_domains[$b]['stop'] )
						{
							break;
						}
					}
				}
				if( $priority === false )
				{
					$priority = $unprioritised;
				}
				$depth = substr_count($url_bits['path'],'/');
				$e_lru = $this->db->escape(strrev($url_list[$a]));
				$e_lr = $this->db->escape(substr(strrev($url_list[$a]),0,2));
				$sql = 'SELECT `url_id` AS `id` , `url_domain_priority` AS `priority`  FROM `urls` WHERE `url_url_sub` = "'.$e_lr.'" AND `url_url` = "'.$e_lru.'"';
				$result = $this->db->fetch_1_row($sql);
				if( $result === null )
				{
					$sql = '
INSERT INTO `urls`
(
	 `url_url`
	,`url_url_sub`
	,`url_depth`
	,`url_domain_priority`
)
VALUES
(
	 "'.$e_lru.'"
	,"'.$e_lr.'"
	,'.$depth.'
	,'.$priority.'
)';
					$this->db->query($sql);

				}
				elseif( $result['priority'] != $priority )
				{
					$this->db->query('UPDATE `urls` SET `url_domain_priority` = '.$priority.' WHERE `url_id` = '.$result['id']);
				}
			}
		}
	}

/**
 * @method _make_priority_domains_usable() takes an indexed array of
 *	   strings and builds a two dimensional array where the
 *	   second level provides meta info to help matching.
 *
 * @param array $input_priority_domains list of domains and or
 *	  subsites that have priority
 *
 * @return array two dimensional array where the first level is an
 *	   index and the second level contains mata info about the
 *	   domain
 */
	private function _make_priority_domains_usable( $input_priority_domains )
	{
		if( !is_array($input_priority_domains) )
		{
			// throw
			return array();
		}
		else
		{
			$output_priorities_domains = array();
			$tmp_test = array();
			foreach( $input_priority_domains as $worthless_key => $value )
			{
				if( is_string($value) )
				{
					if( $value != '' )
					{
						$value = trim($value); // get rid of whitespace
						$chars = strlen($value); // strlen is used to speed up string matching using substr

						if( substr_count($value,'/') )
						{
							$domain_part = explode('/',$value);
							// Check if the domain for this site is already in the priority list.
							$tmp_found = array_search($domain_part[0],$tmp_test);
							if( $tmp_found !== false )
							{
								// the domain which this site is a child of has been listed.
								// change its stop status to fals so it doesn't block giving
								// this site a lower priority than the parent domain
								$output_priority_domains[$tmp_found]['stop'] = false;
							}
						}
						$output_priority_domains[] = array( 'chars' => $chars , 'url' => $value , 'stop' => true );
						$tmp_test[] = $value;
					}
					// else  discard empty strings
				}
				// else discard non strings

			}
			return $output_priority_domains;
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
				$http = $this->curl->check_url("http://{$url_list[$a]['url']}");
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
	public function get_urls_to_warm( $limit_start = true )
	{
		$gmt = gmdate('Y-m-d H:i:s');
		$now = '"'.$this->db->escape($gmt).'"';
		$now_time = strtotime($gmt);

		if( $limit_start === false )
		{
			$limit_start = 0;
		}
		else
		{
			$limit_start = $this->limit_start;
		}

		$sql = '
SELECT	 `urls`.`url_id` AS `id`
	,REVERSE(`urls`.`url_url`) AS `url`
	,`url_by_protocol`.`url_by_protocol_https` AS `https`
	,`url_by_protocol`.`url_by_protocol_cache_expires` AS `expires`
FROM	 `urls`
	,`url_by_protocol`
WHERE	`urls`.`url_id` = `url_by_protocol`.`url_by_protocol__url_id`
AND	`urls`.`url__url_status_id` = '.$this->db->get_cached_id('good','_url_status').'
AND	`url_by_protocol`.`url_by_protocol_ok` = 1
AND	`url_by_protocol`.`url_by_protocol_is_cached` = 1
AND	`url_by_protocol`.`url_by_protocol_cache_expires` < '.$now.$this->_order_by.'
LIMIT '.$limit_start.','.$this->get_urls_count;
		$result = $this->db->fetch_($sql);
		if( $result !== null )
		{
			$output = array();
			$c = count($result);
			for( $a = 0 ; $a < $c ; $a += 1 )
			{
				if( $result[$a]['https'] == 1 )
				{
					$result[$a]['url'] = "https://{$result[$a]['url']}";
				}
				else
				{
					$result[$a]['url'] = "http://{$result[$a]['url']}";
				}
				$result[$a]['now'] = $gmt;
				$result[$a]['age'] = ( $now_time - strtotime($result[$a]['expires']) );
				$output[] = $result[$a];
				unset($result[$a]);
			}
			return $output;
		}
		elseif( $this->limit_start > 0 )
		{
			return $this->get_urls_to_warm( false );
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

/**
 * @method set_order_by() allows you to set the priority order for
 *	   which URLs get cached first
 *
 * @param stirng $input list of columns to order by separated by
 *	  comma, space or underscore
 *	  available columns are:
 *		cache
 *		depth
 *		domain
 *
 * @return boolean TRUE if _order_by was updated. FALSE otherwise
 */
	public function set_order_by( $input )
	{
		if( !is_string($input) )
		{
			return false;
		}
		$spitter = false;
		if( substr_count($input,',') )
		{
			$splitter = ',';
		}
		elseif( substr_count($input,' ') )
		{
			$splitter = ' ';
		}
		elseif( substr_count($input,'_') )
		{
			$splitter = '_';
		}
		if( $splitter !== false )
		{
			$input = explode($splitter,strtolower($input));
			$fields = array('expires','domain','depth');
			$sql = "\nORDER BY ";
			$sep = '';
			for( $a = 0 ; $a < count($input) ; $a += 1 )
			{
				switch($input[$a])
				{
					case 'expires':
					case 'cache':
						$input[$a] = 'expires';
						if( in_array($input[$a],$fields) )
						{
							$sql .= $sep.'`url_by_protocol`.`url_by_protocol_cache_expires` DESC';
							$sep = "\n\t,";
							unset($fields[0]);
						}
						break;
					case 'priority':
					case 'domain':
						$input[$a] = 'domain';
						if( in_array($input[$a],$fields) )
						{
							$sql .= $sep.'`urls`.`url_domain_priority` DESC';
							$sep = "\n\t,";
							unset($fields[1]);
						}
						break;
					case 'level':
					case 'depth':
						$input[$a] = 'depth';
						if( in_array($input[$a],$fields) )
						{
							$sql .= $sep.'`urls`.`url_depth` ASC';
							$sep = "\n\t,";
							unset($fields[2]);
						}
						break;
				}
			}
			if( $sql != "\nORDER BY " )
			{
				$this->_order_by = $sql;
				return true;
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
		$downloaded = $this->curl->check_url( $url_info['url'] , false );
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
		// TODO implement logging feature to record info about URLs being hit.
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
	public function get_get_urls_count()
	{
		return $this->get_urls_count;
	}
}
