# How cachewarm3 should work

## DB:

The database would only need two tables:

### *URLs* table

Store the URL string, cache expiry times and which protocol(s) the
URL can use

	url_id			mediumint(6) primary
	url_url			text
	url_url_sub 		CHAR(2)
	url_HTTP_ok		tinyint(1) default 1
	url_HTTP_is_cache	datetime NULL
	url_HTTP_cache_expires	datetime NULL
	url_HTTPS_ok		tinyint(1) default 0
	url_HTTPS_is_cache	datetime NULL
	url_HTTPS_cache_expires	datetime NULL


### *place* table

stores (if necessary) which ID was last checked

	place_name	varchar(32) NOT NULL unique
	place_url_id	mediumint(6)

When using the URLs list as a source for URLs to be searched it is
useful to be able to kill the processes after a given amount of time
to minimise memory leakage.


## Inserting URLs into the DB

(run daily)

1.	URLs list downloaded from matrix

2.	new URLs:

	1.	get headers for both `HTTPS` and `HTTP` versions of the URL

	2.	insert URL and cache expiry time into URLs table

3.	old URLs:

	1	get headers to see if Page is still available

	2	delete entries for URLs where pages are no longer available


## Warming cache

Get entries for X number of URLs who is cache has expired.

If there are any URLs with cold cache

1.	download page and headers for either `HTTP` or `HTTPS` or `HTTPS & HTTP`

2.	update cache expiry times.

If there are no URLs with cold cache

1.	work out when next URL's cache goes cold

2.	sleep until then.


## Other applications

With the above DB structure we can use the URLs for things like the full text search of web pages.

