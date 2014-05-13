How to install Cachewarmer3

1  Set up a MySQL database
   NOTE: In theory, it's possible to use PostgreSQL or SQLite but
	 classes that manage those connections have not been tested.
	 All feed back and patches welcome.

2  Copy config.default.php to config.php

3  Copy config_db.default.php to config_db.php

4  Edit the valuses of config.php to suit your environment.

5  Edit the valuses of config_db.php to suit your environment.

6  Set up a cron for url_updater.cli.php to run daily.
   NOTE: The initial fun of url_update.cli.php will take a long time
   	 to run depending on how many URLs it has to load into the DB
	 and discover the cache for. (My initial run took about 8 hours.)

7  Run url_updater.cli.php manually. This will help confirm that all
   your config settings work. It will also provide data for use by
   cachewarmer3.cli.php
  
8  Set up a cron for cachewarm_auto-restart.cron.sh to run every
   minute.

   cachewarm_auto-restart.cron.sh checks to see if the appropriate
   number of instances of cachewarmer3.cli.php are running. If not,
   it starts one. If so, it just exits.

   By default only one instance of cachewarmer3.cli.php can run at
   any given moment. To increase this, pass an integer to
   cachewarm_auto-restart.cron.sh 
   e.g. './cachewarm_auto-restart.cron.sh 5' to allow for 5 instances
   	of cachewarmer3.cli.php

   NOTE: I don't have any Windows sys admin skills so if you're a
	 Windows shop, you'll have to wing it. (If you write a windows
	 equivelant, please submit it and I'll add it to the repo)

