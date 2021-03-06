<?php


class curl_get_cache extends curl_get_url_simple
{
//	protected $gmt_offset = 0;
	public $httpobject = null;
	private $save_locally = null;


/**
 * @method __construct() sets up all the details for a cURL connectin
 *
 * @param multi $cookie boolean or string file name.
 *	  If false, don't use cookies at all.
 *	  If true use cookies but store them in the object's cookie
 *	  property.
 *	  If string, store them the file with the location string
 * @param array $login_stuff associative array of login form fields
 *	  and values to be submitted when cURL is attempting to log
 *	  into the desired website
 * @param array $proxy associative array of login form fields and
 *	  values to be used when cURL is attempting to negotiate 
 *	  connecting to the internet through a proxy
 * @param array $httpauth associative array of key/value pairs
 *	  required for HTTP authentication
 *
 * @return object curl_get_url for easily getting content from an
 *	   external website.
 */
	public function __construct( $cookie = true , $proxy = array() , $httpauth = array() )
	{
		parent::__construct( $cookie , $proxy , $httpauth );

		$this->httpobject = new HTTPobject( '' , true );

//		$this->save_locally = new cache_downloaded($this,'');

//		$serverOffset = new DateTime( 'now' , new DateTimeZone( date_default_timezone_get() ) );
//		$this->gmt_offset = $serverOffset->getOffset();
	}

/**
 * @method check_url() tries to pull a given URL from a website thus
 *	   causing the URL to be cached if it isn't already.
 *
 * @param string $url the URL to be downloaded
 *
 * @param boolean $headers_only whether or not to only retrieve the
 *	  page headers, rather than the whole page
 *
 * @return array associative with whether or not the page was
 *	   available, if it was cached and when the cache next expires
 */
	public function check_url( $url , $headers_only = true )
	{
		if( !is_string($url) )
		{
			// throw
		}
		$url = trim($url);
		if( !$this->valid_url($url) )
		{
			// throw
		}
		if( $headers_only !== false )
		{
			$headers_only = true;
		}
		else
		{
			$headers_only = false;
		}

		$output = array(
			 'is_valid' => 0
			,'is_cached' => 0
			,'expires' => null
			,'date' => null
			,'max-age' => 0
			,'s-maxage' => 0
		);
		$this->httpobject->reset_http();
		$this->httpobject->extract_headers( $this->get_content($url,$headers_only,true) );

		if( $this->httpobject->successful_download() === true )
		{
			$output['is_valid'] = 1;
			if( $this->httpobject->is_cached() === true )
			{
				$output['is_cached'] = 1;
				$output['expires'] = $this->httpobject->get_header('expires');
				$output['date'] = $this->httpobject->get_header('date');
				$output['max-age'] = $this->httpobject->get_header('cache_max-age');
				$output['s-maxage'] = $this->httpobject->get_header('cache_s-maxage');
				if( $output['max-age'] === null )
				{
					$output['max-age'] = 0;
				}
				if( $output['s-maxage'] === null )
				{
					$output['s-maxage'] = 0;
				}
			}
//			if( $headers_only === false )
//			{
//				$this->save_locally->write_to_file_system($url,$this->httpobject);
//			}
		}
		return $output;
	}

	public function warm_url( $url )
	{
		return $this->check_url( $url , false );
	}

	public function set_save_locally( cache_downloaded $save_local )
	{
		$this->save_locally = $save_local;
	}
}
