<?php


class curl_get_cache extends curl_get_url
{
	$gmt_offset = 0;
	$httpobject = null;

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
	public function __construct( $cookie = true , $login_stuff = array() , $proxy = array() , $httpauth = array() )
	{
		parent::__construct( $cookie , $login_stuff , $proxy , $httpauth );

		$this->httpobject = new HTTPobject( '' , true );

		$serverOffset = new DateTime( 'now' , new DateTimeZone( date_default_timezone_get() ) );
		$this->gmt_offset = $serverOffset->getOffset();
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
			 'is_valid' => false
			,'is_cached' => false
			,'expiry' => null
		)
		$headers = $this->httpobject( $this->get_content($url,'',$no_body,true) );
		if( $headers->successfull_download() === true )
		{
			if( $headers->is_cached() === true )
			{
				$output['is_cached'] = true;
				$output['expiry'] = $headers->get_header('expiry-raw') + $this->gmt_offset();
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
			 'http' => $this->check_url($http);
			,'https' => $this->check_url($https);
			,'raw_url' => substr_replace( $http , '' , 0 , 7 )
		);
	}

	public function warm_url( $url )
	{
		return $this->check_url( $url , false )
	}
}
