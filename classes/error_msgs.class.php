<?php

class error_msgs
{
	protected $msg_list = array( 'error' => array() , 'warning' => array() , 'notice' => array() );
	protected $list_count = array( 'error' => 0 , 'warning' => 0 , 'notice' => 0 );

	public function __construct() {}

/**
 * @method set_msg() adds error message strings to the existing list
 * of errors
 *
 * @param string comma separated list of error message strings
 *	NOTE:   if first param is matches one of the following, it
 *		will define the message type.
 *			'error'
 *			'warning'
 *			'notice'
 * @return void
 */
	public function set_msg()
	{
		$msgs = func_get_args();
		$e_count = func_num_args();
		$b = 0;

		$msg_type = preg_replace('/s$/', '' , strtolower($msgs[0]) );
		switch($msg_type)
		{
			case 'error': $trace = debug_backtrace();debug($trace);
			case 'warning':
			case 'notice':
				++$b;
				break;
			case 'message':
			case 'note':
			case 'msg':
				$msg_type = 'notice';
				++$b;
				break;
			default:
				$msg_type = 'notice';
		}

		for( $a = $b ; $a < $e_count ; ++$a )
		{
			$tm = preg_replace( '/^0(\.[0-9]+) ([0-9]+)$/' , '\2\1' , microtime() );
			if( is_string($msgs[$a]) && $msgs[$a] != '' )
			{
				$this->msg_list[$msg_type][$tm] = $msgs[$a];
				++$this->list_count[$msg_type];
			}
			elseif( is_int($msgs[$a]) )
			{
				if( isset( $trace[$msgs[$a]] ) )
				{
//					this->msg_list[$msg_type][$ltm] .= ;
				}
			}
			$ltm = $tm;

		}
	}


/**
 * @method get_msg() returns all or the last X error messages
 *
 * @params if paramater is an integer and greater than 0, it will
 *	define the number of messages returned (if sorting by message
 *	type is turned off) or the number of each messages from each
 *	type returned.
 *	if paramater is a string and is equal to one of the following
 *	it sets an appropriate flag
 *		error = return error messages
 *		warnings = return warning messages
 *		notice = return notice messages
 *		by-type = sort output by type
 *	
 * @return array two dimensional array where the first dimension is
 *	keyed by either type or time and the second dimension is
 *	keyed by the alternate. If by-type is set then and the number
 *	of messages returned is defined, then the most recent X
 *	messages will be returned. If by-type is set, then the most
 *	recent X messages of each type will be returned.
 */
	public function get_msg()
	{
		$output_count = 0;
		$by_type = false;
		$msg_types = array( 'error' , 'warning' , 'notice' );
		$msg_args = func_get_args();

		if( func_num_args() > 0 )
		{
			$types_custom = false;
			foreach( $msg_args as $args )
			{
				$custom_type = '';
				if( is_int($arg) && $arg > 0 )
				{
					$output_count = $arg;
				}
				elseif( is_string($arg) )
				{
					$arg = preg_replace('/s$/', '' , strtolower($arg));
					switch($arg)
					{
						case 'notice':
						case 'error':
						case 'warning':
							$custom_type = $arg;
							break;
						case 'message':
						case 'note':
						case 'msg':
							$custom_type = 'notice';
							break;
						case 'by type':
						case 'by-type':
						case 'by_type':
						case 'bytype':
							$by_type = true;
							break;
					}
				}
				if( $custom_type != '' )
				{
					if( $types_custom === false )
					{
						$types_custom = true;
						$msg_types = array();
					}
					$msg_types[] = $custom_type;
				}
			}
		}

		if( $by_type === false )
		{
			$output = array();
			foreach( $msg_types as $msg_type )
			{
				$tmp_msg_type = array();
				foreach( $this->msg_list[$msg_type] as $key => $value )
				{
					$tmp_msg_type[$key] = array( $msg_type => $value );
				}
				array_merge( $output , $tmp_msg_type );
			}
			if( $output_count == 0 )
			{
				ksort($output);
				return $output;
			}
			else
			{
				krsort($output);
				$less_output = array();
				foreach( $output as $key => $value )
				{
					$less_output[$key] = $value;
					--$output_count;
					if( $output_count == 0 )
					{
						break;
					}
				}
				ksort($less_output);
				return $less_output;
			}
		}
		else
		{
			$output = array();
			foreach( $msg_types as $msg_type )
			{
				if( $output_count == 0 )
				{
					$output[$msg_type] = $this->msg_list($msg_type);
				}
				else
				{
					krsort( $this->msg_list[$msg_type] );
					$a = $output_count;
					foreach($this->msg_list[$msg_type] as $key => $value )
					{
						$output[$msg_type][$key] = $value;
						--$a;
						if( $a == 0 )
						{
							break;
						}
					}
				}

			}
		}
	}

}
