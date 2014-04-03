<?php


class curl_get_cache extends curl_get_url_simple
{
//	protected $gmt_offset = 0;
	protected $httpobject = null;

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

//		$serverOffset = new DateTime( 'now' , new DateTimeZone( date_default_timezone_get() ) );
//		$this->gmt_offset = $serverOffset->getOffset();
	}

	public function check_url( $url , $no_body = true )
	{
		if( !is_string($url) )
		{
			// throw
		}
		$url = trim($url);
		if( strlen($url) < 11 ||  ( substr($url,0,7) != 'http://' && substr($url,0,8) != 'https://' ) )
		{
			// throw
		}
		if( $no_body !== false )
		{
			$no_body = true;
		}
		$output = array(
			 'is_valid' => 0
			,'is_cached' => 0
			,'expires' => null
			,'date' => ''
		);
		$this->httpobject->extract_headers( $this->get_content($url,'',$no_body,true) );
//		$output['date-raw'] = $this->httpobject->get_header('date-raw');
//		$output['expires-raw'] = $this->httpobject->get_header('expires-raw');
		if( $this->httpobject->successful_download() === true )
		{
			$output['is_valid'] = 1;
			if( $this->httpobject->is_cached() === true )
			{
				$output['is_cached'] = 1;
				$output['expires'] = $this->httpobject->get_header('expires');
			}
		}
		$this->httpobject->reset_http();
		return $output;
	}

	public function check_url_both( $url )
	{
		if( !is_string($url) )
		{
			// throw
		}
		$url = trim($url);
		if( strlen($url) < 11 || substr($url,0,4) != 'http' )
		{
			// throw
		}
		if( substr($url,0,8) == 'https://' )
		{
			$https = $url;
			$http = substr_replace( $url , 'http' , 0 , 5 );
		}
		else
		{
			$http = $url;
			$https = substr_replace( $url , 'https' , 0 , 4 );
		}
		return array(
			 'http' => $this->check_url($http)
			,'https' => $this->check_url($https)
			,'raw_url' => substr_replace( $http , '' , 0 , 7 )
		);
	}

	public function warm_url( $url )
	{
		return $this->check_url( $url , false );
	}
}
