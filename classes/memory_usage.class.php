<?php

class memory_usage
{
	private $memory_limit = 50;
	private $unit_conversion = 1048576;

	public function __construct( $memory_limit = false , $units = 'mb' )
	{
		if( is_string($units) )
		{
			$units = strtolower($units);
			switch( $units )
			{
				case 'gb':
				case 'gigabytes':
				case 'gigabites':
					$unit_conversion = ( 1024 * 1024 * 1024 );
					break;
				case 'mb':
				case 'megabytes':
				case 'megabites':
					$unit_conversion = ( 1024 * 1024 );
					break;
				case 'kb':
				case 'kilobytes':
				case 'kilobites':
					$unit_conversion = 1024;
					break;
				case 'b':
				case 'bytes':
				case 'bites':
					$unit_conversion = 1;
					break;
			}
		}
		if( is_int($memory_limit) && $memory_limit > 0 )
		{
			$this->memory_limit = $memory_limit;
		}
		$absolute_limit = ini_get('memory_limit');debug($absolute_limit);
		if( $absolute_limit > 0 && $absolute_limit < $this->memory_limit )
		{	
			$this->memory_limit = $absolute_limit;
		}
		debug($this->memory_limit);
	}

	public function get_memory_MB()
	{
		return round( ( memory_get_usage() / $this->unit_conversion ) , 3 );
	}

	public function get_available_MB()
	{
		return ( $this->memory_limit - $this->get_memory_MB() );
	}

	public function have_enough()
	{
		if( $this->get_available_MB() > 0 )
		{
			return true;
		}
		return false;
	}
}
