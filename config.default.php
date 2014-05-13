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


/**
 * @var array $priority_sites list of sites that should be given
 *	priority when warming.
 *
 * If the script has insufficient resources to warm all URLs within
 * the cache expiry time, This helps prioritise which URLs should be
 * warmed.
 *
 * NOTE: URLs are already prioritised by how deep within a site they
 *	 are.
 *	 e.g. in the site hierarchy, pages that are direct children
 *	      of the home page are given top priority. The children
 *	      of those pages are given second priority and so on
 *	 by listing a domain you are saying this domain is more
 *	 important than domains that are not listed AND that this
 *	 domain is more important than domains that are listed after
 *	 it
 *
 * NOTE ALSO: subsites can be given lower (or higher) priority than
 *	 their parent site.
 *
 * e.g. $priority_sites = array(
 *		 'my.domain.net'
 *		,'www.domain.net'
 * 		,'other.domain.net'
 *		,'www.domain.net/lower_priority'
 *	)
 *
 * URLs under 'www.domain.net/lower_priority' have a lower priority
 * than others under 'www.domain.net'. URLs whos sites are not listed
 * in the priority list, all have the same value (in this case 4).
 *	'my.domain.net' has priority 0 and
 *	'www.domain.net/lower_priority' has priority 3
 *	all unlisted sites will have priority 4.
 */
$priority_sites = array();

/**
 * @var string $order_by comma separated list of fields by which to
 *	prioritise URLs to whose cache is to be warmed
 *
 *	fields that can be sorted by are:
 *		'site'  -  for installs that warm multiple sites
 *			   concurrently this prioritises by site
 *			   (only relevant if you list sites above)
 *		'depth' -  how deep within a hierarchical site the
 *			   page the URL points to is
 *		'expiry' - when the cache expires for that URL
 *
 * The default order_by value is 'depth,cache'
 *
 * This is particularly relevant when you have more URLs to warm than
 * you can warm before the cache expires
 */
$order_by = 'depth,cache';


/**
 * @var numeric $throttle_rate the maximum number of URLs that can be
 *	warmed per second.
 *
 * NOTE: a throttle rate of less than 1 is converted to seconds per
 *	 URL e.g. a throttle_rate of 0.2 = 1 url every 5 seconds.
 *	 A throttle rate of less than zero means no throttling.
 */
$throttle_rate = -1;

