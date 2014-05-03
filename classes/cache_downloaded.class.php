<?php

class cache_downloaded
{
	public function __construct( curl_get_url_simple $curl_obj , $local_path )
	{
	}

	public function write_to_file_system( $url , HTTPobject $httpobject )
	{
		true;
	}
}
class cache_locally extends cache_downloaded
{
	private $local_path = '';
	private $curl = null;

	private $ok = false;

	public function __construct( curl_get_url_simple $curl_obj , $local_path )
	{
		if( !is_dir($local_path) )
		{
			if( preg_match('`^(.*?)(?<=\/)([^\/]+)\/$`',$local_path,$path_bits) )
			{
				if( !is_dir($path_bits[1]) || !is_writable($path_bits[1]) )
				{
					// throw
				}
				else
				{
					$path = realpath($path_bits[1]).'/'.$path_bits[2];
					if( !mkdir($path) )
					{
						// throw
					}
					if( substr($path,-1) != '/' )
					{
						$path .= '/';
					}
					mkdir($path.'css');
					mkdir($path.'js');
					mkdir($path.'images');
					mkdir($path.'fonts');
					$this->local_path = $path;
					$this->ok = true;
				}

			}
		}
		$this->curl = $curl_obj;
		if( !is_writable($local_path) )
		{
			// throw
		}
		$this->local_path = $local_path;
		$this->ok = true;
	}

	public function write_to_file_system( $url , HTTPobject $httpobject )
	{
		$url_bits = $this->curl->get_url_parts($url);
		if( !is_dir($url_bits['domain']))
		{
			mkdir( $this->local_path.'/'.$url_bits['domain'].$url_bits['path'],true );
		}
		if( $httpobject->get_content() != '' )
		{
			$tmp = file_put_contents($this->local_path.'/'.$url_bits['domain'].$url_bits['path'].$url_bits['file'],$httpobject->get_content());
			if( $tmp != false )
			{
				return true;
			}
		}
		return false;
		
	}
}

class cache_all_locally extends cache_locally
{
}

class cache_matrix_locally extends cache_all_locally
{
}
