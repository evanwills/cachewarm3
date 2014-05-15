<?php

class throttle
{
	private $_throttle_rate = -1;

	private $_throttle_time = 0;

	private $_throttle_method = '_dont_throttle';


	public function __construct( $rate )
	{
		debug($rate);sleep(1);
		if( is_numeric($rate) && $rate > 0 )
		{
			$this->_throttle_rate = ( 1 / $rate );
			$this->_throttle_method = '_do_throttle';
			$this->_throttle_time = microtime(true);debug($this);
		}
	}


	public function throttle()
	{
		$this->{$this->_throttle_method}();
	}

	private function _dont_throttle() { }
	
	private function _do_throttle()
	{
		$now = microtime(true);
		$time_span = microtime(true) - $this->_throttle_time;
		if( $time_span < $this->_throttle_rate )
		{
			$sleep_time = round( ( $this->_throttle_rate - $time_span ) * 1000000 );
			usleep($sleep_time);
		}
		$this->_throttle_time = microtime(true);
	}
}
