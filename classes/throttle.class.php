<?php

class throttle
{
	private $_throttle_rate = -1;

	private $_throttle_time = 0;

	private $_throttle_method = 'dont_throttle';


	public function __construct( $rate )
	{
		if( is_numeric($rate) && $rate > 0 )
		{
			$this->_throttle_rate = ( 1 / $rate );
			$this->_throttle_method = '_do_throttle';
			$this->_throttle_time = microtime(true);
		}
	}


	public function throttle()
	{
		$this->{$this->_throttle_method}();
	}

	private function _dont_throttle() { }
	
	private function _do_throttle()
	{
		$time_span = microtime(true) - $this->_throttle_time;
		if( $time_span < $this->throttle_time )
		{
			$sleep_time = ( ( $this->throttle_time - $time_span ) / 1000000 );
			usleep($sleep_time);
		}
		$this->throttle_time = microtime(true);
	}
}
