<?php

/**
 * @ver string $source_list URL for page listing all the URLs to be
 *	added to the database, whose cache should be warmed.
 *
 * NOTE: The page downloaded should contain only a list of URLs:
 *	 1 URL per line.
 *	 white space before and after the URL will be removed.
 *
 * NOTE ALSO: As each URL is processed, it's headers are retrieved
 *	  for both HTTP and HTTPS protocols.
 *	- Invalid and/or inaccessible URLs (for either HTTP or HTTPS)
 *	  will be entered into the DB but will be marked as invalid
 *	  and thus ignored when warming.
 *	- URLs (for either HTTP or HTTPS) that are not cached will be
 *	  marked accordingly and no attempt will be made to warm them.
 */
$source_list = '';

/**
 * @var bool $use_local_file whether or not to use a local file as
 *		the source URL list
 *
 * NOTE: It is possible, that you have so many pages to warm, your
 *		 CMS cannot generate a single list of URL and so you
 *		 generate the list of URLs some other way (like using
 *		 url_list_builder.sh) and store that list of URLs locally.
 *		 This allows you to explicitly specify that.
 */
$use_local_file = false;

/**
 * @var string $parent_source_list URL for page listing all the URL
 *		listing pages that make up the actual source_list.
 *
 * This is used by url_list_builder.sh
 */
$parent_source_list = '';

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
// START: Warming priorities


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
 * than others under 'www.domain.net'. URLs whose sites are not listed
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


// END: Warming priorities
// ==================================================================
// START: Memory management


/**
 * @var integer $memory_limit the number of Mega Bytes the script can
 *	use before it should exit.
 *
 * NOTE: The more instances of cachewarmer3.cli.php you are running
 *	 concurrently, the lower the $memory_limit needs to be to
 *	 ensure it doesn't consume all the server's resources.
 */
$memory_limit = 50;

/**
 * @var integer $batch_size the number of URLs to be processes in a
 *	cycle
 *
 * NOTE: The more instances of cachewarmer3.cli.php you run
 *	 concurrently, the smaller the batch size should be.
 */
$batch_size = 10;


// END: Memory management
// ==================================================================
// START: Proxy server care


/**
 * @var numeric $throttle_rate the maximum number of URLs that can be
 *	warmed per second.
 *
 * NOTE: a throttle rate of less than 1 is converted to seconds per
 *	 URL e.g. a $throttle_rate of 0.2 = 1 URL every 5 seconds.
 *		  a $throttle_rate of 2 = 1 URL every half a second
 *		  or two URLs per second
 *	 A $throttle_rate of zero or less means no throttling.
 */
$throttle_rate = -1;


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


// END: Proxy server care
// ==================================================================
// START: Google Analytics credentials

// All the code for GA stuff has been written but not tested!

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
// =================================================================


/**
 * @var string $root absolute path to the cachewarm3 directory
 */
$root = dirname(__FILE__).'/';


/**
 * @var string $cls absolute path to the classes directory.
 */
$cls = $root.'classes/';


/**
 * @var string $local_cache_path absolute path to where the local
 *	cache sould be stored
 *
 * NOTE: this is currently not working
 */
$local_cache_path = '';

