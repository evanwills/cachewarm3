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
	run than are running, it starts up another instance. If no
	more instances can be run, it terminates.
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

By setting the 'order_by' variable in config.php you can choose which
has priority. The default is 'depth,cache' which means that pages that
are closer to the home page get warmed before pages that are deeper
within the site. I'm running the script for a CMS that hosts 14,000+
URLs on multiple sites (20,000 when you include the HTTPS versions of
the same URL). So I've got priority sites listed as well
(see config.default.php).

My 'order_by' is set to 'sites,depth,cache'. This means that cold
URLs are ordered by site priority, then by how close they are to the
home page in the hierarchy, then by how cold the cache is for that
page. I also run 5 instances of the script (which is all the server
it runs on can handle). This gets to about 30% of all the URLs that
need to be warmed in a given period.


Benifits:

There are two benifits to this script: Cache works as it should. And
pages load fast because pages are always in the cache. Our page load
time went from between 3 and 10 seconds to load a page to around 0.1
seconds per page.


Drawbacks:

Depending on how many URLs you have and how quickly the cache expires
for those URLs relative to how much grunt the server you hoste
cachewarmer3 on is you may not be able to warm enough of your URLs.
You then need to consider which URLs you wish to prioritise for warming.

Cachewarmer3 can consume a lot of server resources if you let it.
Versions 1 and 2 of the cachewarmer were both rewritten because they
consumed way too much of the server's RAM, due to PHPs issue with not
releasing memory for string variables when the value of the variable
is made ''. Cachewarmer 3 acknowledges this limitation and so keeps
track of the amount of memory it consumes. When that limit is reached,
it exits (freeing all the memory) and is restarted by
cachewarm_auto-restart.cron.sh sometime in the next minute.

By default, Cachewarmer3's memory limit is 50MB. It probably shouldn't
be set lower than 30MB. Otherwise it may it may exit too soon.

The othe major issue is that if your CMS logs every hit and you have
a lot of pages, you may need to increase the space alocated to server
logs. (We had to do this for our CMS)


What's left to do:

1 Managing URLs that are deemed to be bad / unreachable

2 Better reporting/logging of what's going on.

