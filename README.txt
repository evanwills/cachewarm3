cachewarmer3
Author Evan Wills
Version 0.9

Cachewarmer3 is intended to be run for websites or content management
systems that use a proxy to cache their pages.


Why:
Systems that use a proxy to cache their pages usually do so because
there is a time overhead when generating web pages. This time over
head can lead to a multi second delay between a user requesting a URL
and the server being able to deliver the URL.

A proxy intercepts that request and checks to see if it has that page
in stored and if the cache for that page has expired yet. If the
cache hasn't expired, the proxy server just sends out the page. If
the cache has expired the proxy requests a new copy of the cache.

On large sites, it's not unusual for some pages to go cold. That is,
for the copy stored in the proxy to be out of date. Thus, despite the
proxy, it takes many seconds for the page to be served to the user.

The purpose of this script is to download pages as their cache
expires (before a user requests it) so that when a user requests the
page there is always a warm copy of the cache in the proxy.


How:
It is assumed that the cachewarmer is run automatically by a server.
It has a number of parts that work independantly but together:

1  url_updater.cli.php which downloads the latest list of URLs
	to be warmed and adds any new ones to the database
	(run daily)

2  cachewarmer3.cli.php which gets a list of URLs who's cache has
	expired from the database then downloads the URL's contents
	(This script has a limit to the memory foot print it can
	 occupy. It terminates when that memory limit is reached)

3  cachewarm_auto-restart.cron.sh checks to how many instances of
	cachewarmer3.cli.php are running. If more instances can be
	run than are running, it starts up another instance.
	(run every minute)

NOTE:  for the CMS this was written for, one instance was not enough
	to warm all the URLs whose cache has expired. We needed 25
	instances running concurrently. But we didn't have a server
	that could run that many instances so I implemented a couple
	of levels of prioritisation.


Prioritisation:
If you have a site with too many URLs to warm in the time given for
the cache to expire, you need a way of prioritising which URLs get
warmed first.

cachewarmer3 does this in two ways:
1   by analysing the depth of the URL within the site hierarchy
    (referred to as 'depth') and
2   by asigning a priority to the website site via an array in
    config.php.

Note: for a URL to be warmed it's cache has to have already expired

On large sites, it's possible for many URLs to expire at the same
time. Thus you can also prioritise by cache expiry time.

By setting the 'order_by' variable in the config you can choose which
has priority. The default is 'depth,cache' which means that pages who
are closer to the home page get warmed before pages that are deeper
within the site. I'm running the script on a CMS that hosts 14,000+
URLs on multiple sites. So I've got priority sites listed as well
(see config.default.php). My 'order_by' is set to 'sites,depth,cache'.



