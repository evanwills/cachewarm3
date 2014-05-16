<?php

/**
 * @ver string $source_list URL for page listing all the URLs whose
 *	cache should be added to the database
 */
$source_list = '';


// ==================================================================
// START: Database credentials

/**
 * @var string $db_host the host domain or IP address for the MySql
 *	Database server
 */

$db_host = 'localhost';
/**
 * @var string $db_name the name of the database all the info is
 *	stored in.
 */

$db_name = 'DATABASE NAME';
/**
 * @var string $db_user the username for the account that has access
 *	to the database
 */

$db_user = 'DATABASE USERNAME';
/**
 * @var string $db_pass the passwrod for the account that has access
 *	to the database
 */

$db_pass = 'DATABASE PASSWORD';


// END: Database credentials
// ==================================================================
// START: Google Analytics credentials

/**
 * @var string $ga_email the email address for an account that has
 *	access to the Google Analytics for the sites whose cache is
 *	to be warmed
 */
$ga_email = '';

/**
 * @var string $ga_password the password for the account defined by
 *	$ga_email
 */
$ga_password = '';

/**
 * @var string $ga_profile_id the Google Analytics ID for the profile
 *	from which you want to extract the analytics
 */
$ga_profile_ID = '';

// END: Google Analytics credentials
// ==================================================================

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
 *		'depth'  - how deep within a hierarchical site the
 *			   page the URL points to is
 *		'expiry' - when the cache expires for that URL
 *		'rank'   - Page rank according to Google Analytics'
 *			   pageviews in a given period
 *		'warmed' - When the URL was last warmed
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


/**
 * @var integer $batch_size the number of URLs to be processes in a
 *	cycle
 *
 * NOTE: The more instances of cachewarmer3.cli.php you run
 *	 concurrently, the smaller the batch size should be.
 */
$batch_size = 10;


/**
 * @var integer $revisit_in minimum number of minutes before a URL
 *	can be warmed again
 *
 * NOTE: it's MINUTES not seconds, or hours (or miliseconds)
 *
 * NOTE: this is hear because I encountered a problem where our
 *	 squid proxy was serving up pages who's cache has expired.
 *	 This caused the script to hit those pages over and over
 *	 again. Which in turn caused the squid logs to explode and
 *	 take down the squid server. I then added throttle_rate to
 *	 limit how quickly Squid could be hit. But didn't fix the
 *	 problem of hitting the same cold cache pages.
 */
$revisit_in = 0;

