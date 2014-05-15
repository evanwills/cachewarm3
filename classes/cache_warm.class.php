<?php

class cache_warm
{
	protected $_db = null;
	protected $_curl = null;
	protected $_batch_size = 100;
	protected $_limit_start = 0;
	protected $_order_by = '
ORDER BY `urls`.`url_site_priority` ASC
	,`urls`.`url_depth` ASC
	,`url_by_protocol`.`url_by_protocol_cache_expires` DESC';

/**
 * @method __construct()
 *
 * @param right_db $db database connection object
 *
 * @param curl_get_cache $curl cURL object to get the contents of URLs
 *
 * @param integer $instance When warming the URLs, the warming is
 *	  done in batches. If you are running multiple instances of
 *	  the warming script. $instance represents the order number
 *	  of the instance so that there is no overlap in URLs that are
 *	  warmed.
 *
 * @param integer $batch_size the number of URLs to be processed in
 *	  each batch
 * 	  NOTE:  The higher the number of instances you plan to run
 *	 	 concurrently, the smaller the batch size should be.
 *		 Otherwise, there will be an overlap
 *
 * @param string $priority_order the order in which to prioritise
 *	  which URLs get warmed first
 */
	public function __construct( right_db $db , curl_get_cache $curl , $instance = 0 , $batch_size = 100 )
	{
		// set the db object
		$this->_db = $db;

		// cache any normalised tables in the DB to minimise the need for
		/// table joins
		$this->_db->cache_normalised();

		// set the cURL object
		$this->_curl = $curl;

		$this->set_batch_size($batch_size);

		// set limit start
		if( is_int($instance) && $instance > 0 )
		{
			$this->_limit_start = ( $instance * $this->_batch_size );
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
		// get current GMT date/time
		$gmt = gmdate('Y-m-d H:i:s');

		// make current GMT date/time OK for use in SQL
		$now = '"'.$this->_db->escape($gmt).'"';
		$now_time = strtotime($gmt);

		// check to see if we need to get the first lot of
		// URLs or if we can go to another page of URLs that
		// need to be warmed
		if( $limit_start === false )
		{
			$limit_start = 0;
		}
		else
		{
			$limit_start = $this->_limit_start;
		}

		// Select the URLs to be warmed.
		$sql = '
SELECT	 `urls`.`url_id` AS `id`
	,REVERSE(`urls`.`url_url`) AS `url`
	,`url_by_protocol`.`url_by_protocol_https` AS `https`
	,`url_by_protocol`.`url_by_protocol_cache_expires` AS `expires`
--	,`urls`.`url_site_priority` AS `priority`
FROM	 `urls`
	,`url_by_protocol`
WHERE	`urls`.`url_id` = `url_by_protocol`.`url_by_protocol__url_id`
AND	`urls`.`url__url_status_id` = '.$this->_db->get_cached_id('good','_url_status').'
AND	`url_by_protocol`.`url_by_protocol_ok` = 1
AND	`url_by_protocol`.`url_by_protocol_is_cached` = 1
AND	`url_by_protocol`.`url_by_protocol_cache_expires` < '.$now.$this->_order_by.'
LIMIT '.$limit_start.','.$this->_batch_size;
		$result = $this->_db->fetch_($sql);
		if( $result !== null )
		{
			$output = array();
			$c = count($result);
			for( $a = 0 ; $a < $c ; $a += 1 )
			{
				// Add the protocol to the URL
				if( $result[$a]['https'] == 1 )
				{
					$result[$a]['url'] = "https://{$result[$a]['url']}";
				}
				else
				{
					$result[$a]['url'] = "http://{$result[$a]['url']}";
				}
				// Add the GMT now time to the array for this URL
				$result[$a]['now'] = $gmt;
				// Add the difference between the $gmt now time and the cache expiration time
				$result[$a]['age'] = ( $now_time - strtotime($result[$a]['expires']) );
				$output[] = $result[$a];
				unset($result[$a]);
			}
			return $output;
		}
		elseif( $this->_limit_start > 0 )
		{
			// OK there were no URLs in the page we selected, lets try from the top.
			return $this->get_urls_to_warm( false );
		}
		else
		{
			// OK there were no URLs at all to warm (This is great!!!)
			// Let's report how long it is before the next URL expires.
			$sql = '
SELECT	`url_by_protocol_cache_expires` AS `expires`
FROM	`url_by_protocol`
WHERE	`url_by_protocol_ok` = 1
AND	`url_by_protocol_is_cached` = 1
AND	`url_by_protocol_cache_expires` >= '.$now.'
ORDER BY `url_by_protocol_cache_expires` DESC
LIMIT 0,1';
			$next_expires = $this->_db->fetch_1($sql);
			if( $next_expires !== null )
			{
				// return the number of seconds till the cache expires for the next URL
				return strtotime($next_expires) - $now_time;
			}
		}
		// That's bad. Couldn't do anything... This should not happen.
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
 *		site
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
					case 'site':
					case 'sites':
					case 'priority':
					case 'domain':
					case 'domains':
						$input[$a] = 'domain';
						if( in_array($input[$a],$fields) )
						{
							$sql .= $sep.'`urls`.`url_site_priority` DESC';
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


/**
 * @method warm_url() attempts to warm the cache of an individual URL
 *
 * NOTE: If a URL could not be downloaded, it checks to see if a URL
 *	 with the alternate protocol was successfully downloaded. If
 *	 not it updates the entry for the URL to mark it's status as
 *	 bad. Another script at a later stage will attempt to
 *	 download the URL again. If that fails, the URL will be
 *	 marked for deletion. One final attempt will be made download
 *	 the URLs marked for deletion before the URL is finally
 *	 deleted from the DB.
 *
 * @param array $url_info containing the URL and some metadata about
 *	  that URL
 *
 * @return boolean TRUE if the URL was successfully downloaded. FALSE
 *	   otherwise.
 */
	public function warm_url( $url_info )
	{
		if( !is_array($url_info) || !isset($url_info['url']) || !isset($url_info['sub']) || !isset($url_info['http']) || !isset($url_info['https']) )
		{
			// throw
		}
		$downloaded = $this->_curl->check_url( $url_info['url'] , false );debug($url_info,$downloaded);
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
			if( $this->_db->fetch_1($sql) == 1 )
			{
				$status = 'bad';
			}
		}
		// TODO implement logging feature to record info about URLs being hit.
		$sql = "
UPDATE	 `urls`
	,`url_by_protocol`
SET	 `urls`.`url__url_status_id` = ".$this->_db->get_cached_id($status,'_url_status')."
	,`url_by_protocol_ok` = {$downloaded['is_valid']}
	,`url_by_protocol_is_cached` = {$downloaded['is_cached']}";
		if( $downloaded['expires'] !== null )
		{
			$sql .= "\n\t,`url_by_protocol_cache_expires` = '".$this->_db->escape(date('Y-m-d H:i:s',$downloaded['expires']))."'";
		}
		$sql .= "
WHERE	`urls`.`url_id` = {$url_info['id']}
AND	`urls`.`url_id` = `url_by_protocol`.`url_by_protocol__url_id`
AND	`url_by_protocol`.`url_by_protocol_https` = {$url_info['https']}";

		$this->_db->query($sql);

		return $output;
	}

	public function set_batch_size( $count )
	{
		if( is_int($count) && $count > 0 )
		{
			$this->_batch_size = $count;
			return true;
		}
		return false;
	}
	public function get_batch_size()
	{
		return $this->_batch_size;
	}

}

class cache_warm_check_urls extends cache_warm
{

/**
 * @method update_url_list() takes a URL that points to a page that
 *	   lists all the URLs you want warmed (one URL per line) and
 *	   adds any new URLs to the database
 *
 * @param string $source_url the URL for the page listing URLs.
 *
 * @param array $priority_sites list of sites (listed from most
 *	  important to least important) by which to prioritise
 *	  warming order. This is primarily for when you don't have
 *	  enough resources on the this side to warm all URLs before
 *	  their cache expires.
 *
 * @return boolean TRUE if any URLs were added or updated.
 *	   FALSE otherwise
 */
	public function update_url_list( $source_url , $priority_sites = array() )
	{
		if( !$this->_curl->valid_url($source_url) )
		{
			// throw
			return false;
		}

		// get the URLs and put them into an array.
		$url_list = explode("\n",$this->_curl->get_content($source_url));

		// make the list of priority_sites more usable by this method
		$priority_sites = $this->_make_priority_sites_usable( $priority_sites );

		// unprioritised sites get the lowest priority.
		$unprioritised = count($priority_sites);

		$output = false;

		for( $a = 0 ; $a < count($url_list) ; $a += 1 )
		{
			$url_list[$a] = trim($url_list[$a]);

			// validate the URL and get metadata about it
			$url_bits = $this->_curl->get_url_parts($url_list[$a]);
			if( $url_bits != false )
			{
				$url = $url_bits['domain'].$url_bits['path'].$url_bits['file'];

				// strip the protocol and leading slashes (it's redundant data in the DB)
				if( $url_bits['protocol'] == 'https' )
				{
					$url_list[$a] = substr($url_list[$a],8);
				}
				else
				{
					$url_list[$a] = substr($url_list[$a],7);
				}

				// work out the site priority of the URL
				$priority = false;
				for( $b = 0 ; $b < $unprioritised ; $b += 1 )
				{
					if( substr( $url_list[$a] , 0 , $priority_sites[$b]['chars'] ) == $priority_sites[$b]['url'] )
					{
						$priority = $b;
						if( $priority_sites[$b]['stop'] )
						{
							break;
						}
					}
				}
				if( $priority === false )
				{
					$priority = $unprioritised;
				}

				// work out how deep within the site the URL points to
				$depth = substr_count($url_bits['path'],'/');

				// to speed up searching, we reverse the URL on the premis
				// that, as the domain is included in the URL, and the domain
				// is likely to be consistant across a large number of URLs
				// then having to search the domain needlessly adds time to
				// the search where as the reversed URL is much more random
				// and so the search will progress quicker 
				$e_lru = $this->_db->escape(strrev($url_list[$a]));

				// to further help improve search speed we add the last two
				// characters of the URL to it's own indexed column
				$e_lr = $this->_db->escape(substr(strrev($url_list[$a]),0,2));

				// See if the current URL is already listed in the DB
				$result = $this->_db->fetch_1_row(
					'SELECT `url_id` AS `id` , `url_site_priority` AS `priority`  FROM `urls` WHERE `url_url_sub` = "'.$e_lr.'" AND `url_url` = "'.$e_lru.'"'
				);
				if( $result === null || ( is_array($result) && empty($result) ) )
				{
					// OK not listed. Let's add it to the DB
					$sql = '
INSERT INTO `urls`
(
	 `url_url`
	,`url_url_sub`
	,`url_depth`
	,`url_site_priority`
)
VALUES
(
	 "'.$e_lru.'"
	,"'.$e_lr.'"
	,'.$depth.'
	,'.$priority.'
)';
					$this->_db->query($sql);
					$output = true;
				}
				elseif( $result['priority'] != $priority )
				{
					// Yep, it's aready in the DB but the priority has changed.
					$this->_db->query('UPDATE `urls` SET `url_site_priority` = '.$priority.' WHERE `url_id` = '.$result['id']);
					$output = true;
				}
			}
		}
		return $output;
	}

/**
 * @method _make_priority_sites_usable() takes an indexed array of
 *	   strings and builds a two dimensional array where the
 *	   second level provides meta info to help matching.
 *
 * @param array $input_priority_sites list of domains and or
 *	  subsites that have priority
 *
 * @return array two dimensional array where the first level is an
 *	   index and the second level contains mata info about the
 *	   domain
 */
	private function _make_priority_sites_usable( $input_priority_sites )
	{
		if( !is_array($input_priority_sites) )
		{
			// throw
			return array();
		}
		else
		{
			$output_priorities_domains = array();
			$tmp_test = array();
			foreach( $input_priority_sites as $worthless_key => $value )
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
								$output_priority_sites[$tmp_found]['stop'] = false;
							}
						}
						// Add the URL and metadata to the list of priority sites.
						$output_priority_sites[] = array( 'chars' => $chars , 'url' => $value , 'stop' => true );
						$tmp_test[] = $value;
					}
					// else  discard empty strings
				}
				// else discard non strings

			}
			return $output_priority_sites;
		}
	}

/**
 * @method check_new_urls() finds any URLs in the DB marked as new
 *	   and grabs their headers to see if the URLs is good, if the
 *	   page is cached and, if so, when the cache expires
 *
 * @return $output TRUE if there were new URLs to check. False otherrwise 
 */
	public function check_new_urls()
	{
		$output = false;
		$sql = '
SELECT	 `url_id` AS `id`
	,REVERSE(`url_url`) AS `url`
FROM	`urls`
WHERE	`url__url_status_id` = '.$this->_db->get_cached_id('new','_url_status').'
ORDER BY `url_depth` ASC';
		$url_list = $this->_db->fetch_($sql);
		if( $url_list !== null )
		{
			$b = 0;
			$url_good_sql_start = $url_good_sql = '
UPDATE `urls`
SET `url__url_status_id` = '.$this->_db->get_cached_id('good','_url_status').'
WHERE `url_id` IN ( ';
			$url_bad_sql_start = $url_bad_sql = '
UPDATE `urls`
SET `url__url_status_id` = '.$this->_db->get_cached_id('bad','_url_status').'
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
				$http = $this->_curl->check_url("http://{$url_list[$a]['url']}");
				$url_ok = false;
				if( $http['is_valid'] == 1 )
				{
					$b += 1;
					$cache_sql .= "$cache_sep( {$url_list[$a]['id']} , 0 , {$http['is_valid']} , {$http['is_cached']} , '{$http['date']}' )";
					$cache_sep = "\n,";
					$url_ok = true;
				}

				// check the https version of the URL
				$https = $this->_curl->check_url("http://{$url_list[$a]['url']}");
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

					$this->_db->query($cache_sql);
					$cache_sql = $cache_sql_start;
					$cache_sep = '';
					if( $url_good_sep != '' )
					{
						$this->_db->query($url_good_sql.' )');
						$url_good_sql = $url_good_sql_start;
						$url_good_sep = '';
						$output = true;
					}
					if( $url_bad_sep != '' )
					{
						$this->_db->query($url_bad_sql.' )');
						$url_bad_sql = $url_bad_sql_start;
						$url_bad_sep = '';
						$output = true;
					}
					$b = 0;
				}
			}
			if( $cache_sep != '' )
			{
				$this->_db->query($cache_sql);
				$output = true;
			}
			if( $url_good_sep != '' )
			{
				$this->_db->query($url_good_sql.' )');
				$output = true;
			}
			if( $url_bad_sep != '' )
			{
				$this->_db->query($url_bad_sql.' )');
				$output = true;
			}
		}
		return $output;
	}
/**
 * @method get_uncached_urls() get a list of URL's that are not cached
 *
 * @param $start
 */
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
		
		$url_list = $this->_db->fetch_($sql);
		if( $url_list != null )
		{
			return $url_list;
		}
		return false;
	}


	public function check_bad_urls()
	{
		// TODO
	}

	public function check_delete_urls()
	{
		// TODO
	}


}

class cache_warm_ga_stats extends cache_warm
{
	private $_ga = null;
	private $_profileID = '';
	private $_top_x_urls = 1000;
	private $_skew_recent = 1;
	private $_skew_distant = 1;
	private $_average = array();
	private $_skew_no_comparison_method ='_skew_no_comparison_raw';
	private $_ga_sleep_time = 10;

/**
 * @method __construct()
 *
 * @param right_db $db database connection object
 *
 * @param curl_get_cache $curl cURL object to get the contents of URLs
 *
 * @param integer $instance When warming the URLs, the warming is
 *	  done in batches. If you are running multiple instances of
 *	  the warming script. $instance represents the order number
 *	  of the instance so that there is no overlap in URLs that are
 *	  warmed.
 *
 * @param integer $batch_size the number of URLs to be processed in
 *	  each batch
 * 	  NOTE:  The higher the number of instances you plan to run
 *	 	 concurrently, the smaller the batch size should be.
 *		 Otherwise, there will be an overlap
 *
 * @param string $priority_order the order in which to prioritise
 *	  which URLs get warmed first
 */
	public function __construct( right_db $db , curl_get_cache $curl , $instance = 0 , $batch_size = 100 )
	{
		parent::__construct( $db , $curl , $urls_count , $limit_start );
	}

	public function set_top_urls_count( $top_urls )
	{
		if( is_int($top_urls) && $top_urls > 10 )
		{
			$this->_top_x_urls = $top_urls;
			return true;
		}
		return false;
	}

	public function set_batch_size( $batch_size )
	{
		if( is_int($batch_size) && $batch_size > 0 )
		{
			$this->_batch_size = $batch_size;	
			return true;
		}
		return false;
	}

	public function set_ga_sleep_time( $seconds )
	{
		if( is_int($seconds) && $seconds >= 0 )
		{
			$this->_ga_sleep_time = $seconds;
			return true;
		}
		return false;
	}

	public function set_skew( $towards , $amount , $no_comparison = 'raw' )
	{
		if( is_string($towards) && is_numeric($amount) && $amount >= 1 && is_string($no_comparison) )
		{
			$towards = strtolower(trim($towards));

			$this->_skew_no_comparison_method = '__skew_no_comparison_raw';

			switch($towards)
			{
				case 'recent':
				case 'recent_past':
				case 'month':
				case 'last_month':
					$this->_skew_distant = 1;
					$this->_skew_recent = $amount;

					$no_comparison = strtolower(trim(no_comparison));

					if( method_exists($this,'_skew_no_comparison_'.$no_comparison) )
					{
						$this->_skew_no_comparison_method = '_skew_no_comparison_'.$no_comparison;
					}
					elseif( substr($no_comparison,0,13) == '_skew_no_comp' && method_exists($this,$no_comparison) )
					{
						$this->_skew_no_comparison_method = $no_comparison;
					}
					return true;
					break;
				case 'distant':
				case 'distant_past':
				case 'year':
				case 'last_year':
					$this->_skew_distant = $amount;
					$this->_skew_recent = 1;
					return true;
					break;
				default:
					$this->_skew_distant = 1;
					$this->_skew_recent = 1;
					return true;
					break;
			}
		}
		return false;
	}


/**
 * @method get_ga_rankings() extracts the page ranking from Google
 *	   Analytics and adds it to the databse to enable URLs to be
 *	   prioritised by GA rank
 *
 * @param gapi $ga Google API object.
 *
 * @param string $profileID google analytics profile ID for the for
 *	  the site you want the data from
 *
 * @return boolean TRUE if ga ranking for URLs was updated. FALSE
 *	   otherwise.
 */
	public function get_ga_rankings( gapi $ga , $profileID )
	{
		if( !is_string($profileID) )
		{
			// throw
			return;
		}
		else
		{
			$this->_profileID = $profile;
		}
		$this->_ga = $ga;


		// TODO fill in this method.
		// for more info see:
		// https://www.techpunch.co.uk/development/googles-analytics-api-php-gapi-interface

		$last_years_from = date( 'Y-m-d' , strtotime( '-370 days' ) );
		$last_years_to = date( 'Y-m-d' , strtotime( '-340 days' ) );
		$last_month_from = date( 'Y-m-d' , strtotime( '-30 days' ) );
		$lsat_month_to = date( 'Y-m-d' );


		// we double the number of required URLs because we assume that some
		// URLs from last year will no longer be available. But we want to
		// match the ranking of as many URLs as possible so to ensure the
		// maximum overlap in URLs we'll just get more.
		$limit = ( $this->_top_x_urls * 2 );

		$start = 0;

		// lets start getting GA data in batches.
		while( $start > $limit )
		{
			$test = 0;
			// Grab stats for this time last year
			$test += $this->_get_ga_report_data( $last_years_from , $last_years_to , $start , 'distant' );

			// Google doesn't like being hit too fast, so we'll sleep for a while
			sleep($this->_ga_sleep_time);

			// Grab stats the last month
			$test += $this->_get_ga_report_data( $last_month_from , $last_month_to , $start , 'recent' );

			// if there are no more URLs to get, we'll just break out of this
			// part of the process
			if( $test == 0 )
			{
				break;
			}
			// Google doesn't like being hit too fast, so we'll sleep for a while
			sleep($this->_ga_sleep_time);

			// start at the next batch.
			$start += $this->_batch_size;
		}

		$average = array();

		// we've got all the rankings, now lets fiddle with them.
		// Because we are assuming that the activity from the month just gone
		// may not be representitive what will happen in the month to come,
		// we'll do some magic to decide what OUR final ranking will be
		foreach( $this->average as $url => $rank )
		{
			$distant = isset($rank['distant'])?$rank['distant']:0;
			$recent = isset($rank['recent'])?$rank['recent']:0;
			if( $recent > 0 )
			{
				// no point in looking at a URL's ranking if there's no current equivalent
				if( $distant == 0 )
				{
					// there's nothing to compare it with so use one of the skew methods.
					// By default we just use the raw $recent value
					$avarage[$url] = $this->{$this->_skew_no_comparison_method}( $distant , $recent );
				}
				else
				{
					// here we multipy the distant rank with the distant skew,
					// then multipy the recent rank with the recent skew,
					// then add them both together and devide it by the distant
					// skew added to the recent skew
					$average[$url] = $this->_skew_no_comparison_standard( $distant , $recent );
				}
			}
		}

		// for tidyness' sake lets just clean out the $average property
		$this->average = null;

		if( !empty($average) )
		{
			// sort the rankings in reverse order.
			arsort($average);

//			while( count($avarage) > $this->top_urls )
//			{
//				array_pop($average);
//			}

			$this->average = $average;
			return true;
		}
		return false;
	}


/**
 * @method get_recent_ga_rankings() extracts the page ranking from Google
 *	   Analytics and adds it to the databse to enable URLs to be
 *	   prioritised by GA rank
 *
 * @param gapi $ga Google API object.
 *
 * @param string $profileID google analytics profile ID for the for
 *	  the site you want the data from
 *
 * @return boolean TRUE if ga ranking for URLs was updated. FALSE
 *	   otherwise.
 */
	public function get_recent_ga_rankings( gapi $ga , $profileID )
	{
		if( !is_string($profileID) )
		{
			// throw
			return;
		}
		else
		{
			$this->_profileID = $profile;
		}
		$this->_ga = $ga;


		// TODO fill in this method.
		// for more info see:
		// https://www.techpunch.co.uk/development/googles-analytics-api-php-gapi-interface

		$last_month_from = date( 'Y-m-d' , strtotime( '-30 days' ) );
		$lsat_month_to = date( 'Y-m-d' );

		$start = 0;

		// lets start getting GA data in batches.
		while( $start > $this->_top_x_urls )
		{
			if( ! $this->_get_ga_report_data( $last_month_from , $last_month_to , $start , 'recent' ) )
			{
				break;
			}
			// Google doesn't like being hit too fast, so we'll sleep for a while
			$start += $this->_batch_size;

			// start at the next batch.
			sleep($this->_ga_sleep_time);
		}

		$average = array();
		foreach( $this->average as $url => $rank )
		{
			$recent = isset($rank['recent'])?$rank['recent']:0;
			if( $recent > 0 )
			{
				$avarage[$url] = $recent;
			}
		}
		// for tidyness' sake lets just clean out the $average property
		$this->average = null;

		// sort the rankings in reverse order.
		arsort($average);
//		while( count($avarage) > $this->_top_x_urls )
//		{
//			array_pop($average);
//		}
		$this->average = $average;
	}

/**
 * @method _get_ga_report_data() grabs the google analytics page
 *	   rankings rankings
 *
 * @param string $from the start date to calculate the rankings
 *
 * @param string $to the end date to calculate the rankings
 *
 * @param integer $start the 
 */
	private function _get_ga_report_data( $from , $to , $start , $distant_recent )
	{
		$most_popular = $this->_ga->requestReportData(
				 $this->prifileID
				,array('pagePath','pageviews') // dimensions
				,array('pageviews') // metrics
				,array('-pageviews')// sort
				,null
				,$from	// start date of ranking period
				,$to	// end date of ranking period
				,$start // batch start position
				,$this->_batch_size // batch size
		);
		if( is_array($most_popular) && !empty($most_popular) )
		{
			foreach( $most_popular as $popular_url )
			{
				// get the details
				$dimensions = $popular_url->getDimensions();

				// add an item in the array if none
				if( !isset($average[$dimension['pagePath']]) )
				{
					$this->_average[$dimension['pagePath']] = array();
				}
				// make sure the value is the correct type
				settype($dimension['pageviews'],'integer');

				// make the URL the key and the rank one child in the URL's rank
				// array
				$this->_average[$dimension['pagePath']][$distant_recent] = $dimension['pageviews'];
			}
			return 1;
		}
		return 0;
	}

	public function persist_ga_rankings()
	{
		if( !empty($this->avarage) )
		{
			foreach( $this->average as $url => $rank )
			{
				$lru = '"'.$this->_db->escape(strrev($url)).'"';
				$lr = '"'.$this->_db->escape(strrev(substr($url,-2,2))).'"';
				$sql = '
UPDATE	`urls`
SET	`url_ga_rank` = '.$rank.'
WHERE	`url_url` = '.$lru.'
AND	`url_url_sub` = '.$lr;
				$this->_db->query($sql);
			}
		}
	}

	private function _skew_no_comparison_raw( $distant , $recent )
	{
		return $recent;
	}

	private function _skew_no_comparison_simple( $distant , $recent )
	{
		return ( ( $recent * ( $this->_skew_recent - 1 ) ) / $this->_skew_recent );
	}

	private function _skew_no_comparison_standard( $distant , $recent )
	{
		return ( ( ( $distant * $this->_skew_distant ) + ( $recent * $this->_skew_recent ) ) / ( $this->_skew_distant + $this->_skew_recent ) );
	}
}
