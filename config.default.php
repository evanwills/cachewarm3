<?php

/**
 * @ver string $source_list URL for page listing all the URLs whose
 *	cache should be added to the database
 */
$source_list = '';

/**
 * @var string $local_cache_path absolute path to where the local
 *	cache sould be stored 
 */
$local_cache_path = '';


/**
 * @var integer $memory_limit the number of Mega Bytes the script can
 *	use before it should exit.
 */
$memory_limit = 50;

/**
 * @var string $root absolute path to the cachewarm3 directory
 */
$root = dirname(__FILE__).'/';


/**
 * @var string $cls absolute path to the classes directory.
 */
$cls = $root.'classes/';

